<?php
declare(strict_types=1);

namespace App\Services;

use InvalidArgumentException;
use PDO;
use RuntimeException;

final class SupplyStockService
{
    public function __construct(private PDO $db) {}

    /** @param array<int, int> $productQuantities @return list<array<string, int|float>> */
    public function lockForSale(array $productQuantities, int $branchId): array
    {
        if ($productQuantities === []) return [];
        $productIds = array_keys($productQuantities);
        sort($productIds, SORT_NUMERIC);
        $holders = implode(',', array_fill(0, count($productIds), '?'));
        $recipe = $this->db->prepare(
            "SELECT pi.id_producto, pi.id_insumo, pi.cantidad, i.nombre
             FROM producto_insumos pi JOIN insumos i ON i.id_insumo = pi.id_insumo
             WHERE pi.id_producto IN ({$holders}) AND i.estado = 'Activo'
             ORDER BY pi.id_insumo, pi.id_producto"
        );
        $recipe->execute($productIds);
        $requirements = [];
        $coveredProducts = [];
        foreach ($recipe->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $productId = (int) $row['id_producto'];
            $supplyId = (int) $row['id_insumo'];
            $coveredProducts[$productId] = true;
            if (!isset($requirements[$supplyId])) {
                $requirements[$supplyId] = ['quantity' => 0.0, 'name' => (string) $row['nombre']];
            }
            $requirements[$supplyId]['quantity'] += (float) $row['cantidad'] * $productQuantities[$productId];
        }
        if (array_diff($productIds, array_keys($coveredProducts)) !== []) {
            throw new InvalidArgumentException('Una bebida no tiene configurado el insumo que consume.');
        }

        $supplyIds = array_keys($requirements);
        sort($supplyIds, SORT_NUMERIC);
        $supplyHolders = implode(',', array_fill(0, count($supplyIds), '?'));
        $sql = "SELECT id_inventario_insumo, id_insumo, stock_actual
                FROM inventario_insumos
                WHERE id_sucursal = ? AND id_insumo IN ({$supplyHolders})
                ORDER BY id_insumo";
        if ($this->db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql') $sql .= ' FOR UPDATE';
        $stockStmt = $this->db->prepare($sql);
        $stockStmt->execute(array_merge([$branchId], $supplyIds));
        $stockBySupply = [];
        foreach ($stockStmt->fetchAll(PDO::FETCH_ASSOC) as $row) $stockBySupply[(int) $row['id_insumo']] = $row;

        $allocations = [];
        foreach ($requirements as $supplyId => $requirement) {
            $row = $stockBySupply[$supplyId] ?? null;
            $available = $row ? (float) $row['stock_actual'] : 0.0;
            $needed = round((float) $requirement['quantity'], 3);
            if ($available + 0.0001 < $needed) {
                throw new InvalidArgumentException('Stock insuficiente de ' . $requirement['name'] . '. Disponible: ' . $this->formatQuantity($available) . '.');
            }
            $allocations[] = [
                'inventory_id' => (int) $row['id_inventario_insumo'],
                'quantity' => $needed,
                'stock_before' => $available,
                'stock_after' => round($available - $needed, 3),
            ];
        }
        return $allocations;
    }

    /** @param list<array<string, int|float>> $allocations */
    public function deductForSale(array $allocations, int $userId, int $saleId): void
    {
        $update = $this->db->prepare(
            'UPDATE inventario_insumos SET stock_actual = stock_actual - ?
             WHERE id_inventario_insumo = ? AND stock_actual >= ?'
        );
        $movement = $this->db->prepare(
            "INSERT INTO movimientos_insumos
             (id_inventario_insumo, id_usuario, tipo, cantidad, stock_anterior, stock_posterior, referencia_tipo, referencia_id)
             VALUES (?, ?, 'Venta', ?, ?, ?, 'ventas_directas', ?)"
        );
        foreach ($allocations as $allocation) {
            $update->execute([$allocation['quantity'], $allocation['inventory_id'], $allocation['quantity']]);
            if ($update->rowCount() !== 1) throw new RuntimeException('El inventario de insumos cambio durante la venta.');
            $movement->execute([
                $allocation['inventory_id'], $userId, -$allocation['quantity'],
                $allocation['stock_before'], $allocation['stock_after'], $saleId,
            ]);
        }
    }

    private function formatQuantity(float $quantity): string
    {
        return rtrim(rtrim(number_format($quantity, 3, '.', ''), '0'), '.');
    }
}
