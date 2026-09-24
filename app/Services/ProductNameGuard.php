<?php
declare(strict_types=1);

namespace App\Services;

use InvalidArgumentException;
use PDO;

final class ProductNameGuard
{
    public function __construct(private PDO $db) {}

    public function assertAvailable(string $name, ?int $excludeProductId = null): void
    {
        $sql = 'SELECT id_producto, estado FROM productos WHERE LOWER(nombre_producto) = LOWER(?)';
        $params = [$name];
        if ($excludeProductId !== null) {
            $sql .= ' AND id_producto <> ?';
            $params[] = $excludeProductId;
        }
        $sql .= ' LIMIT 1';

        $statement = $this->db->prepare($sql);
        $statement->execute($params);
        $existing = $statement->fetch(PDO::FETCH_ASSOC);
        if (!$existing) return;

        $action = ($existing['estado'] ?? '') === 'Inactivo'
            ? 'El producto esta inactivo; reactivalo o editalo desde el catalogo.'
            : 'Edita el producto existente desde el catalogo.';
        throw new InvalidArgumentException("Ya existe un producto con ese nombre. {$action}");
    }
}
