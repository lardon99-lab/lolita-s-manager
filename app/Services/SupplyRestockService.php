<?php
declare(strict_types=1);

namespace App\Services;

use InvalidArgumentException;
use PDO;
use Throwable;

final class SupplyRestockService
{
    private const MAX_LINES = 30;
    private const MAX_QUANTITY = 100000;

    public function __construct(private PDO $db) {}

    /** @return array{receipt_id:int, supplies:int, units:int, duplicate:bool} */
    public function restock(int $branchId, int $userId, array $items, string $key, string $notes = ''): array
    {
        if ($items === [] || count($items) > self::MAX_LINES) throw new InvalidArgumentException('Agrega al menos un insumo.');
        if (trim($key) === '') throw new InvalidArgumentException('No fue posible identificar la recepcion.');
        if (mb_strlen($notes) > 500) throw new InvalidArgumentException('La observacion es demasiado extensa.');
        $normalized = [];
        foreach ($items as $item) {
            $id = filter_var($item['supply_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            $quantity = filter_var($item['quantity'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => self::MAX_QUANTITY]]);
            if ($id === false || $quantity === false) throw new InvalidArgumentException('Cada insumo debe tener una cantidad valida.');
            $normalized[$id] = ($normalized[$id] ?? 0) + $quantity;
            if ($normalized[$id] > self::MAX_QUANTITY) throw new InvalidArgumentException('La cantidad de un insumo excede el limite.');
        }
        $keyHash = hash('sha256', trim($key));

        try {
            $this->db->beginTransaction();
            $existing = $this->db->prepare('SELECT id_abastecimiento, total_productos, total_unidades FROM abastecimientos WHERE id_usuario = ? AND idempotency_key = ?');
            $existing->execute([$userId, $keyHash]);
            $receipt = $existing->fetch(PDO::FETCH_ASSOC);
            if ($receipt) {
                $this->db->commit();
                return ['receipt_id' => (int) $receipt['id_abastecimiento'], 'supplies' => (int) $receipt['total_productos'], 'units' => (int) $receipt['total_unidades'], 'duplicate' => true];
            }

            $header = $this->db->prepare('INSERT INTO abastecimientos (id_sucursal, id_usuario, idempotency_key, observaciones) VALUES (?, ?, ?, ?)');
            $header->execute([$branchId, $userId, $keyHash, trim($notes) !== '' ? trim($notes) : null]);
            $receiptId = (int) $this->db->lastInsertId();
            $select = $this->db->prepare(
                "SELECT ii.id_inventario_insumo, ii.stock_actual, i.nombre
                 FROM inventario_insumos ii JOIN insumos i ON i.id_insumo = ii.id_insumo
                 WHERE ii.id_sucursal = ? AND ii.id_insumo = ? AND i.estado = 'Activo' FOR UPDATE"
            );
            $update = $this->db->prepare('UPDATE inventario_insumos SET stock_actual = stock_actual + ? WHERE id_inventario_insumo = ?');
            $movement = $this->db->prepare(
                "INSERT INTO movimientos_insumos
                 (id_inventario_insumo, id_usuario, tipo, cantidad, stock_anterior, stock_posterior, referencia_tipo, referencia_id)
                 VALUES (?, ?, 'Abastecimiento', ?, ?, ?, 'abastecimientos', ?)"
            );
            foreach ($normalized as $supplyId => $quantity) {
                $select->execute([$branchId, $supplyId]);
                $supply = $select->fetch(PDO::FETCH_ASSOC);
                if (!$supply) throw new InvalidArgumentException('Uno de los insumos no esta disponible en la sucursal.');
                $before = (float) $supply['stock_actual'];
                $after = round($before + $quantity, 3);
                $update->execute([$quantity, $supply['id_inventario_insumo']]);
                $movement->execute([$supply['id_inventario_insumo'], $userId, $quantity, $before, $after, $receiptId]);
            }
            $units = array_sum($normalized);
            $finish = $this->db->prepare('UPDATE abastecimientos SET total_productos = ?, total_unidades = ? WHERE id_abastecimiento = ?');
            $finish->execute([count($normalized), $units, $receiptId]);
            (new AuditService($this->db))->record('supplies.restocked', 'abastecimientos', $receiptId, $branchId, ['insumos' => count($normalized), 'unidades' => $units]);
            $this->db->commit();
            return ['receipt_id' => $receiptId, 'supplies' => count($normalized), 'units' => $units, 'duplicate' => false];
        } catch (Throwable $error) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $error;
        }
    }
}
