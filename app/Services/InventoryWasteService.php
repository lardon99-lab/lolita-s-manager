<?php
declare(strict_types=1);

namespace App\Services;

use InvalidArgumentException;
use PDO;
use RuntimeException;

final class InventoryWasteService
{
    public function __construct(private PDO $db)
    {
    }

    /** @return array{branch_id: int, allocations: list<array{inventory_id: int, quantity: int, stock_before: int, stock_after: int}>} */
    public function lockProductStock(int $productId, int $branchId, int $quantity): array
    {
        $sql = 'SELECT id_inventario, id_sucursal, stock_actual
                FROM inventario
                WHERE id_producto = ? AND id_sucursal = ? AND stock_actual > 0
                ORDER BY fecha_caducidad IS NULL ASC, fecha_caducidad ASC, id_inventario ASC';
        if ($this->db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql') $sql .= ' FOR UPDATE';

        $statement = $this->db->prepare($sql);
        $statement->execute([$productId, $branchId]);

        return ['branch_id' => $branchId, 'allocations' => $this->allocate($statement->fetchAll(PDO::FETCH_ASSOC), $quantity)];
    }

    /** @return array{branch_id: int, allocations: list<array{inventory_id: int, quantity: int, stock_before: int, stock_after: int}>} */
    public function lockInventoryLot(int $inventoryId, int $quantity): array
    {
        $sql = 'SELECT id_inventario, id_sucursal, stock_actual FROM inventario WHERE id_inventario = ?';
        if ($this->db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql') $sql .= ' FOR UPDATE';

        $statement = $this->db->prepare($sql);
        $statement->execute([$inventoryId]);
        $lot = $statement->fetch(PDO::FETCH_ASSOC);
        if (!$lot) throw new InvalidArgumentException('El registro de inventario no existe.');

        return ['branch_id' => (int) $lot['id_sucursal'], 'allocations' => $this->allocate([$lot], $quantity)];
    }

    /** @param list<array{inventory_id: int, quantity: int, stock_before: int, stock_after: int}> $allocations */
    public function deduct(array $allocations, int $userId, string $reason): void
    {
        $update = $this->db->prepare('UPDATE inventario SET stock_actual = stock_actual - ? WHERE id_inventario = ? AND stock_actual >= ?');
        $waste = $this->db->prepare('INSERT INTO mermas (id_inventario, id_usuario, cantidad, motivo) VALUES (?, ?, ?, ?)');
        $movement = $this->db->prepare(
            "INSERT INTO movimientos_inventario
             (id_inventario, id_usuario, tipo, cantidad, stock_anterior, stock_posterior, motivo)
             VALUES (?, ?, 'Merma', ?, ?, ?, ?)"
        );

        foreach ($allocations as $allocation) {
            $update->execute([$allocation['quantity'], $allocation['inventory_id'], $allocation['quantity']]);
            if ($update->rowCount() !== 1) throw new RuntimeException('El inventario cambio durante la merma. Intenta nuevamente.');

            $waste->execute([$allocation['inventory_id'], $userId, $allocation['quantity'], $reason]);
            $movement->execute([
                $allocation['inventory_id'], $userId, -$allocation['quantity'],
                $allocation['stock_before'], $allocation['stock_after'], $reason,
            ]);
        }
    }

    /** @param list<array<string, mixed>> $lots @return list<array{inventory_id: int, quantity: int, stock_before: int, stock_after: int}> */
    private function allocate(array $lots, int $quantity): array
    {
        $available = array_sum(array_map(static fn (array $lot): int => (int) $lot['stock_actual'], $lots));
        if ($available < $quantity) {
            throw new InvalidArgumentException("No puedes mermar {$quantity} unidades. Stock disponible: {$available}.");
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

        return $allocations;
    }
}
