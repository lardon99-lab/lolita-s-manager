<?php
declare(strict_types=1);

namespace App\Services;

use DateTimeImmutable;
use InvalidArgumentException;
use PDO;
use PDOException;
use RuntimeException;
use Throwable;

final class InventoryRestockService
{
    private const MAX_LINES = 50;
    private const MAX_LINE_QUANTITY = 100000;
    private const MAX_TOTAL_QUANTITY = 1000000;

    public function __construct(private PDO $db)
    {
    }

    /**
     * @param list<array<string, mixed>> $items
     * @return array{receipt_id: int, products: int, units: int, duplicate: bool}
     */
    public function restock(
        int $branchId,
        int $userId,
        array $items,
        string $idempotencyKey,
        string $notes = ''
    ): array {
        if ($branchId < 1 || $userId < 1) {
            throw new InvalidArgumentException('La sucursal y el usuario son obligatorios.');
        }
        if ($items === [] || count($items) > self::MAX_LINES) {
            throw new InvalidArgumentException('Agrega entre 1 y ' . self::MAX_LINES . ' productos.');
        }
        if (mb_strlen($notes) > 500) {
            throw new InvalidArgumentException('Las observaciones no pueden superar 500 caracteres.');
        }

        $keyHash = hash('sha256', trim($idempotencyKey));
        if (trim($idempotencyKey) === '') {
            throw new InvalidArgumentException('No fue posible identificar la recepcion. Recarga la pagina.');
        }

        $normalized = $this->normalizeItems($items);
        $totalUnits = array_sum(array_column($normalized, 'quantity'));
        if ($totalUnits > self::MAX_TOTAL_QUANTITY) {
            throw new InvalidArgumentException('La recepcion excede el limite total permitido.');
        }

        try {
            $this->db->beginTransaction();
            $existing = $this->findReceipt($userId, $keyHash);
            if ($existing !== null) {
                $this->db->commit();
                return $existing + ['duplicate' => true];
            }

            $receipt = $this->db->prepare(
                'INSERT INTO abastecimientos
                 (id_sucursal, id_usuario, idempotency_key, observaciones)
                 VALUES (?, ?, ?, ?)'
            );
            $receipt->execute([$branchId, $userId, $keyHash, $notes !== '' ? $notes : null]);
            $receiptId = (int) $this->db->lastInsertId();

            $products = $this->lockProducts($branchId, array_column($normalized, 'product_id'));
            $update = $this->db->prepare(
                'UPDATE inventario SET stock_actual = stock_actual + ? WHERE id_inventario = ?'
            );
            $insert = $this->db->prepare(
                'INSERT INTO inventario
                 (id_sucursal, id_producto, stock_actual, stock_minimo, fecha_caducidad)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $movement = $this->db->prepare(
                "INSERT INTO movimientos_inventario
                 (id_inventario, id_usuario, tipo, cantidad, stock_anterior, stock_posterior, referencia_tipo, referencia_id)
                 VALUES (?, ?, 'Abastecimiento', ?, ?, ?, 'abastecimientos', ?)"
            );

            foreach ($normalized as $item) {
                $product = $products[$item['product_id']] ?? null;
                if ($product === null) {
                    throw new InvalidArgumentException('Uno de los productos no pertenece a la sucursal seleccionada.');
                }
                if ($product['status'] !== 'Activo') {
                    throw new InvalidArgumentException('El producto ' . $product['name'] . ' no esta activo.');
                }

                $expiry = $this->resolveExpiry($item['expiry'], $product['shelf_life']);
                $lot = $this->findLot($product['lots'], $expiry);
                if ($lot !== null) {
                    $inventoryId = $lot['inventory_id'];
                    $stockBefore = $lot['stock'];
                    $update->execute([$item['quantity'], $inventoryId]);
                    if ($update->rowCount() !== 1) {
                        throw new RuntimeException('El inventario cambio durante la recepcion. Intenta nuevamente.');
                    }
                } else {
                    $stockBefore = 0;
                    $insert->execute([$branchId, $item['product_id'], $item['quantity'], $product['minimum'], $expiry]);
                    $inventoryId = (int) $this->db->lastInsertId();
                }

                $movement->execute([
                    $inventoryId,
                    $userId,
                    $item['quantity'],
                    $stockBefore,
                    $stockBefore + $item['quantity'],
                    $receiptId,
                ]);
            }

            $finish = $this->db->prepare(
                'UPDATE abastecimientos SET total_productos = ?, total_unidades = ? WHERE id_abastecimiento = ?'
            );
            $finish->execute([count($normalized), $totalUnits, $receiptId]);
            (new AuditService($this->db))->record(
                'inventory.restocked',
                'abastecimientos',
                $receiptId,
                $branchId,
                ['productos' => count($normalized), 'unidades' => $totalUnits]
            );
            $this->db->commit();

            return [
                'receipt_id' => $receiptId,
                'products' => count($normalized),
                'units' => $totalUnits,
                'duplicate' => false,
            ];
        } catch (Throwable $error) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            if ($error instanceof PDOException && $error->getCode() === '23000') {
                $existing = $this->findReceipt($userId, $keyHash);
                if ($existing !== null) return $existing + ['duplicate' => true];
            }
            throw $error;
        }
    }

    /** @param list<array<string, mixed>> $items @return list<array{product_id: int, quantity: int, expiry: ?string}> */
    private function normalizeItems(array $items): array
    {
        $grouped = [];
        foreach ($items as $item) {
            $productId = filter_var($item['product_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            $quantity = filter_var($item['quantity'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => self::MAX_LINE_QUANTITY]]);
            if ($productId === false || $quantity === false) {
                throw new InvalidArgumentException('Cada producto debe tener una cantidad valida.');
            }
            $expiry = trim((string) ($item['expiry'] ?? '')) ?: null;
            $groupKey = $productId . '|' . ($expiry ?? '');
            if (!isset($grouped[$groupKey])) {
                $grouped[$groupKey] = ['product_id' => $productId, 'quantity' => 0, 'expiry' => $expiry];
            }
            $grouped[$groupKey]['quantity'] += $quantity;
            if ($grouped[$groupKey]['quantity'] > self::MAX_LINE_QUANTITY) {
                throw new InvalidArgumentException('La cantidad de un producto excede el limite permitido.');
            }
        }
        return array_values($grouped);
    }

    /**
     * @param list<int> $productIds
     * @return array<int, array{name: string, status: string, shelf_life: int, minimum: int, lots: list<array{inventory_id: int, stock: int, expiry: ?string}>}>
     */
    private function lockProducts(int $branchId, array $productIds): array
    {
        $productIds = array_values(array_unique(array_map('intval', $productIds)));
        sort($productIds, SORT_NUMERIC);
        $holders = implode(',', array_fill(0, count($productIds), '?'));
        $sql = "SELECT i.id_inventario, i.id_producto, i.stock_actual, i.stock_minimo, i.fecha_caducidad,
                       p.nombre_producto, p.estado, p.dias_vida_util
                FROM inventario i
                JOIN productos p ON p.id_producto = i.id_producto
                WHERE i.id_sucursal = ? AND i.id_producto IN ({$holders})
                ORDER BY i.id_producto, i.id_inventario";
        if ($this->db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql') $sql .= ' FOR UPDATE';
        $statement = $this->db->prepare($sql);
        $statement->execute(array_merge([$branchId], $productIds));

        $products = [];
        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $id = (int) $row['id_producto'];
            if (!isset($products[$id])) {
                $products[$id] = [
                    'name' => (string) $row['nombre_producto'],
                    'status' => (string) $row['estado'],
                    'shelf_life' => (int) $row['dias_vida_util'],
                    'minimum' => (int) $row['stock_minimo'],
                    'lots' => [],
                ];
            }
            $products[$id]['lots'][] = [
                'inventory_id' => (int) $row['id_inventario'],
                'stock' => (int) $row['stock_actual'],
                'expiry' => $row['fecha_caducidad'] !== null ? (string) $row['fecha_caducidad'] : null,
            ];
        }
        return $products;
    }

    private function resolveExpiry(?string $expiry, int $shelfLife): ?string
    {
        if ($shelfLife <= 0) return null;
        if ($expiry === null) return (new DateTimeImmutable('today'))->modify("+{$shelfLife} days")->format('Y-m-d');

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $expiry);
        $errors = DateTimeImmutable::getLastErrors();
        if ($date === false || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
            throw new InvalidArgumentException('La fecha de caducidad no es valida.');
        }
        if ($date < new DateTimeImmutable('today')) {
            throw new InvalidArgumentException('La fecha de caducidad no puede estar en el pasado.');
        }
        return $date->format('Y-m-d');
    }

    /** @param list<array{inventory_id: int, stock: int, expiry: ?string}> $lots @return array{inventory_id: int, stock: int, expiry: ?string}|null */
    private function findLot(array $lots, ?string $expiry): ?array
    {
        foreach ($lots as $lot) {
            if ($lot['expiry'] === $expiry) return $lot;
        }
        return null;
    }

    /** @return array{receipt_id: int, products: int, units: int}|null */
    private function findReceipt(int $userId, string $keyHash): ?array
    {
        $statement = $this->db->prepare(
            'SELECT id_abastecimiento, total_productos, total_unidades
             FROM abastecimientos WHERE id_usuario = ? AND idempotency_key = ?'
        );
        $statement->execute([$userId, $keyHash]);
        $receipt = $statement->fetch(PDO::FETCH_ASSOC);
        if (!$receipt) return null;
        return [
            'receipt_id' => (int) $receipt['id_abastecimiento'],
            'products' => (int) $receipt['total_productos'],
            'units' => (int) $receipt['total_unidades'],
        ];
    }
}
