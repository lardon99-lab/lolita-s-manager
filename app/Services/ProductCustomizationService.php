<?php
declare(strict_types=1);

namespace App\Services;

use App\Http\Validator;
use App\Security\Auth;
use InvalidArgumentException;
use JsonException;
use PDO;
use Throwable;

final class ProductCustomizationService
{
    public function __construct(private PDO $db) {}

    public function configuration(int $productId, bool $requireManagement = true): array
    {
        if ($requireManagement) {
            Auth::requirePermission('products.manage');
            $this->requireProductAccess($productId);
        }

        $groups = $this->db->prepare(
            "SELECT g.id_grupo, g.nombre, pg.minimo_selecciones, pg.maximo_selecciones
             FROM producto_personalizacion_grupos pg
             JOIN personalizacion_grupos g ON g.id_grupo = pg.id_grupo
             WHERE pg.id_producto = ? AND g.estado = 'Activo'
             ORDER BY pg.orden, g.nombre"
        );
        $groups->execute([$productId]);
        $result = [];
        $options = $this->db->prepare(
            "SELECT o.id_opcion, o.nombre, po.recargo, po.predeterminada
             FROM producto_personalizacion_opciones po
             JOIN personalizacion_opciones o ON o.id_opcion = po.id_opcion
             WHERE po.id_producto = ? AND o.id_grupo = ?
               AND po.estado = 'Activo' AND o.estado = 'Activo'
             ORDER BY po.orden, o.nombre"
        );
        foreach ($groups->fetchAll(PDO::FETCH_ASSOC) as $group) {
            $options->execute([$productId, $group['id_grupo']]);
            $group['opciones'] = $options->fetchAll(PDO::FETCH_ASSOC);
            $result[] = $group;
        }
        return $result;
    }

    public function configurationsForProducts(array $productIds): array
    {
        $result = [];
        foreach (array_values(array_unique(array_map('intval', $productIds))) as $productId) {
            if ($productId > 0) $result[$productId] = $this->configuration($productId, false);
        }
        return $result;
    }

    public function save(array $input): int
    {
        Auth::requirePermission('products.manage');
        $productId = Validator::positiveInt($input['id_producto'] ?? null, 'producto');
        $this->requireProductAccess($productId);
        try {
            $decoded = json_decode((string) ($input['configuracion'] ?? ''), true, 32, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new InvalidArgumentException('La configuracion del producto no es valida.');
        }
        if (!is_array($decoded) || count($decoded) > 10) {
            throw new InvalidArgumentException('La configuracion del producto no es valida.');
        }

        $configuration = $this->validateConfiguration($decoded);
        $startedTransaction = !$this->db->inTransaction();
        if ($startedTransaction) $this->db->beginTransaction();
        try {
            $this->db->prepare('DELETE FROM producto_personalizacion_opciones WHERE id_producto = ?')->execute([$productId]);
            $this->db->prepare('DELETE FROM producto_personalizacion_grupos WHERE id_producto = ?')->execute([$productId]);

            $groupUpsert = $this->db->prepare(
                "INSERT INTO personalizacion_grupos (nombre, estado) VALUES (?, 'Activo')
                 ON DUPLICATE KEY UPDATE id_grupo = LAST_INSERT_ID(id_grupo), estado = 'Activo'"
            );
            $optionUpsert = $this->db->prepare(
                "INSERT INTO personalizacion_opciones (id_grupo, nombre, estado) VALUES (?, ?, 'Activo')
                 ON DUPLICATE KEY UPDATE id_opcion = LAST_INSERT_ID(id_opcion), estado = 'Activo'"
            );
            $productGroup = $this->db->prepare(
                'INSERT INTO producto_personalizacion_grupos (id_producto, id_grupo, minimo_selecciones, maximo_selecciones, orden) VALUES (?, ?, ?, ?, ?)'
            );
            $productOption = $this->db->prepare(
                "INSERT INTO producto_personalizacion_opciones (id_producto, id_opcion, recargo, predeterminada, estado, orden) VALUES (?, ?, ?, ?, 'Activo', ?)"
            );

            foreach ($configuration as $groupOrder => $group) {
                $groupUpsert->execute([$group['nombre']]);
                $groupId = (int) $this->db->lastInsertId();
                $productGroup->execute([$productId, $groupId, $group['minimo'], $group['maximo'], $groupOrder]);
                foreach ($group['opciones'] as $optionOrder => $option) {
                    $optionUpsert->execute([$groupId, $option['nombre']]);
                    $optionId = (int) $this->db->lastInsertId();
                    $productOption->execute([$productId, $optionId, $option['recargo'], $option['predeterminada'] ? 1 : 0, $optionOrder]);
                }
            }

            if ($startedTransaction) $this->db->commit();
            return $productId;
        } catch (Throwable $error) {
            if ($startedTransaction && $this->db->inTransaction()) $this->db->rollBack();
            throw $error;
        }
    }

    private function validateConfiguration(array $groups): array
    {
        $result = [];
        $groupNames = [];
        $optionTotal = 0;
        foreach ($groups as $group) {
            if (!is_array($group)) throw new InvalidArgumentException('Uno de los grupos no es valido.');
            $name = Validator::text($group['nombre'] ?? '', 'grupo', 50);
            $nameKey = mb_strtolower($name);
            if (isset($groupNames[$nameKey])) throw new InvalidArgumentException('No puedes repetir un grupo.');
            $groupNames[$nameKey] = true;

            $options = is_array($group['opciones'] ?? null) ? $group['opciones'] : [];
            if ($options === [] || count($options) > 25) throw new InvalidArgumentException("El grupo {$name} debe tener entre 1 y 25 opciones.");
            $optionTotal += count($options);
            if ($optionTotal > 50) throw new InvalidArgumentException('La configuracion excede el limite de opciones.');

            $maximum = filter_var($group['maximo'] ?? 1, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => count($options)]]);
            if ($maximum === false) throw new InvalidArgumentException("El maximo de selecciones de {$name} no es valido.");
            $minimum = !empty($group['obligatorio']) ? 1 : 0;
            $optionNames = [];
            $validatedOptions = [];
            $defaultCount = 0;
            foreach ($options as $option) {
                if (!is_array($option)) throw new InvalidArgumentException("Una opcion de {$name} no es valida.");
                $optionName = Validator::text($option['nombre'] ?? '', 'opcion', 80);
                $optionKey = mb_strtolower($optionName);
                if (isset($optionNames[$optionKey])) throw new InvalidArgumentException("Hay opciones repetidas en {$name}.");
                $optionNames[$optionKey] = true;
                $isDefault = !empty($option['predeterminada']);
                if ($isDefault) $defaultCount++;
                $validatedOptions[] = [
                    'nombre' => $optionName,
                    'recargo' => Validator::money($option['recargo'] ?? 0, 'recargo', 1000000),
                    'predeterminada' => $isDefault,
                ];
            }
            if ($defaultCount > $maximum) throw new InvalidArgumentException("El grupo {$name} tiene demasiadas opciones predeterminadas.");
            $result[] = ['nombre' => $name, 'minimo' => $minimum, 'maximo' => $maximum, 'opciones' => $validatedOptions];
        }
        return $result;
    }

    private function requireProductAccess(int $productId): void
    {
        $stmt = $this->db->prepare('SELECT DISTINCT id_sucursal FROM inventario WHERE id_producto = ?');
        $stmt->execute([$productId]);
        $branches = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
        $allowed = Auth::allowedBranches('products.manage');
        if ($branches === [] || ($allowed !== null && array_diff($branches, $allowed) !== [])) {
            throw new InvalidArgumentException('No puedes modificar este producto.');
        }
    }
}
