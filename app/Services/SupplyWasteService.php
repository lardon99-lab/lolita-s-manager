<?php
declare(strict_types=1);

namespace App\Services;

use InvalidArgumentException;
use PDO;
use RuntimeException;
use Throwable;

final class SupplyWasteService
{
    public function __construct(private PDO $db) {}

    /** @return array{inventory_id:int, supply_name:string, stock_before:float, stock_after:float} */
    public function record(int $branchId, int $supplyId, int $userId, int $quantity, string $reason): array
    {
        if ($quantity < 1 || $quantity > 100000) {
            throw new InvalidArgumentException('La cantidad de la merma no es valida.');
        }
        $reason = trim($reason);
        if ($reason === '' || mb_strlen($reason) > 255) {
            throw new InvalidArgumentException('El motivo de la merma no es valido.');
        }

        try {
            $this->db->beginTransaction();
            $sql = "SELECT ii.id_inventario_insumo, ii.stock_actual, i.nombre
                    FROM inventario_insumos ii
                    JOIN insumos i ON i.id_insumo = ii.id_insumo
                    WHERE ii.id_sucursal = ? AND ii.id_insumo = ? AND i.estado = 'Activo'";
            if ($this->db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql') $sql .= ' FOR UPDATE';
            $select = $this->db->prepare($sql);
            $select->execute([$branchId, $supplyId]);
            $supply = $select->fetch(PDO::FETCH_ASSOC);
            if (!$supply) throw new InvalidArgumentException('El vaso seleccionado no esta disponible en la sucursal.');

            $before = (float) $supply['stock_actual'];
            if ($before < $quantity) {
                throw new InvalidArgumentException("No puedes mermar {$quantity} unidades. Stock disponible: " . rtrim(rtrim(number_format($before, 3, '.', ''), '0'), '.') . '.');
            }
            $after = round($before - $quantity, 3);
            $inventoryId = (int) $supply['id_inventario_insumo'];
            $update = $this->db->prepare(
                'UPDATE inventario_insumos SET stock_actual = stock_actual - ?
                 WHERE id_inventario_insumo = ? AND stock_actual >= ?'
            );
            $update->execute([$quantity, $inventoryId, $quantity]);
            if ($update->rowCount() !== 1) {
                throw new RuntimeException('El inventario cambio durante la merma. Intenta nuevamente.');
            }

            $movement = $this->db->prepare(
                "INSERT INTO movimientos_insumos
                 (id_inventario_insumo, id_usuario, tipo, cantidad, stock_anterior, stock_posterior, motivo)
                 VALUES (?, ?, 'Merma', ?, ?, ?, ?)"
            );
            $movement->execute([$inventoryId, $userId, -$quantity, $before, $after, $reason]);
            (new AuditService($this->db))->record(
                'supplies.waste_recorded',
                'inventario_insumos',
                $inventoryId,
                $branchId,
                ['id_insumo' => $supplyId, 'cantidad' => $quantity, 'motivo' => $reason]
            );
            $this->db->commit();

            return [
                'inventory_id' => $inventoryId,
                'supply_name' => (string) $supply['nombre'],
                'stock_before' => $before,
                'stock_after' => $after,
            ];
        } catch (Throwable $error) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $error;
        }
    }
}

