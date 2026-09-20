<?php
declare(strict_types=1);

namespace App\Services;

use App\Http\Validator;
use App\Security\Auth;
use InvalidArgumentException;
use PDO;

final class ProductDesignService
{
    public function __construct(private PDO $db) {}

    public function configuration(int $productId): array
    {
        $stmt = $this->db->prepare(
            'SELECT permite_diseno, permite_imagen, recargo_diseno
             FROM producto_diseno_config WHERE id_producto = ?'
        );
        $stmt->execute([$productId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'permite_diseno' => (bool) ($row['permite_diseno'] ?? false),
            'permite_imagen' => (bool) ($row['permite_imagen'] ?? true),
            'recargo_diseno' => round((float) ($row['recargo_diseno'] ?? 0), 2),
        ];
    }

    public function configurationsForProducts(array $productIds): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $productIds))));
        if ($ids === []) return [];

        $holders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->db->prepare(
            "SELECT id_producto, permite_diseno, permite_imagen, recargo_diseno
             FROM producto_diseno_config WHERE id_producto IN ({$holders})"
        );
        $stmt->execute($ids);
        $result = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $result[(int) $row['id_producto']] = [
                'permite_diseno' => (bool) $row['permite_diseno'],
                'permite_imagen' => (bool) $row['permite_imagen'],
                'recargo_diseno' => round((float) $row['recargo_diseno'], 2),
            ];
        }
        return $result;
    }

    public function save(int $productId, array $input): void
    {
        Auth::requirePermission('products.manage');
        if ($productId <= 0) throw new InvalidArgumentException('El producto no es valido.');

        $enabled = filter_var($input['permite_diseno'] ?? false, FILTER_VALIDATE_BOOL);
        $allowImage = $enabled && filter_var($input['permite_imagen'] ?? false, FILTER_VALIDATE_BOOL);
        $surcharge = $enabled
            ? Validator::money($input['recargo_diseno'] ?? 0, 'recargo de diseno', 1000000)
            : 0.0;

        $stmt = $this->db->prepare(
            'INSERT INTO producto_diseno_config
                (id_producto, permite_diseno, permite_imagen, recargo_diseno)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE permite_diseno = VALUES(permite_diseno),
                 permite_imagen = VALUES(permite_imagen), recargo_diseno = VALUES(recargo_diseno)'
        );
        $stmt->execute([$productId, $enabled ? 1 : 0, $allowImage ? 1 : 0, $surcharge]);
    }
}
