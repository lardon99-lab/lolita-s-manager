<?php
declare(strict_types=1);

namespace App\Services;

use InvalidArgumentException;
use PDO;
use RuntimeException;

final class InventoryStockService
{
    public function __construct(private PDO $db)
    {
    }

    /**
     * @return array{price: float, name: string, allocations: array<int, array<string, int>>}
     */
    public function lockForSale(int $productId, int $branchId, int $quantity): array
    {
        $product = $this->db->prepare(
            "SELECT nombre_producto, precio_base
             FROM productos
             WHERE id_producto = ? AND estado = 'Activo'"
        );
        $product->execute([$productId]);
        $productData = $product->fetch(PDO::FETCH_ASSOC);
        if (!$productData) {
            throw new InvalidArgumentException("El producto {$productId} no esta activo.");
        }

        $sql = "SELECT id_inventario, stock_actual
                FROM inventario
                WHERE id_producto = ?
                  AND id_sucursal = ?
                  AND stock_actual > 0
                  AND (fecha_caducidad IS NULL OR fecha_caducidad >= CURRENT_DATE)
                ORDER BY fecha_caducidad IS NULL ASC, fecha_caducidad ASC, id_inventario ASC";
        if ($this->db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql') {
            $sql .= ' FOR UPDATE';
        }

        $lotsQuery = $this->db->prepare($sql);
        $lotsQuery->execute([$productId, $branchId]);
        $lots = $lotsQuery->fetchAll(PDO::FETCH_ASSOC);
        $available = array_sum(array_map(static fn (array $lot): int => (int) $lot['stock_actual'], $lots));
        if ($available < $quantity) {
            $name = (string) $productData['nombre_producto'];
            throw new InvalidArgumentException("Stock insuficiente para {$name}. Disponible: {$available}.");
        }

        $remaining = $quantity;
        $allocations = [];
        foreach ($lots as $lot) {
            if ($remaining === 0) break;
            $stockBefore = (int) $lot['stock_actual'];
            $deduction = min($stockBefore, $remaining);
            $allocations[] = [
                'inventory_id' => (int) $lot['id_inventario'],
                'quantity' => $deduction,
                'stock_before' => $stockBefore,
                'stock_after' => $stockBefore - $deduction,
            ];
            $remaining -= $deduction;
        }

        return [
            'price' => round((float) $productData['precio_base'], 2),
            'name' => (string) $productData['nombre_producto'],
            'allocations' => $allocations,
        ];
    }

    /** @param array<int, array<string, int>> $allocations */
    public function deductForSale(array $allocations, int $userId, int $saleId): void
    {
        $update = $this->db->prepare(
            'UPDATE inventario SET stock_actual = stock_actual - ? WHERE id_inventario = ? AND stock_actual >= ?'
        );
        $movement = $this->db->prepare(
            "INSERT INTO movimientos_inventario
             (id_inventario, id_usuario, tipo, cantidad, stock_anterior, stock_posterior, referencia_tipo, referencia_id)
             VALUES (?, ?, 'Venta', ?, ?, ?, 'ventas_directas', ?)"
        );

        foreach ($allocations as $allocation) {
            $update->execute([
                $allocation['quantity'],
                $allocation['inventory_id'],
                $allocation['quantity'],
            ]);
            if ($update->rowCount() !== 1) {
                throw new RuntimeException('El inventario cambio durante la venta. Intenta nuevamente.');
            }
            $movement->execute([
                $allocation['inventory_id'],
                $userId,
                -$allocation['quantity'],
                $allocation['stock_before'],
                $allocation['stock_after'],
                $saleId,
            ]);
        }
    }
}
