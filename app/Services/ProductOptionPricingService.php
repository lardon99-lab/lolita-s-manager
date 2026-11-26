<?php
declare(strict_types=1);

namespace App\Services;

use App\Http\Validator;
use InvalidArgumentException;
use PDO;

final class ProductOptionPricingService
{
    public function __construct(private PDO $db) {}

    /** @return array{surcharge: float, options: list<array<string, mixed>>} */
    public function price(int $productId, array $selected): array
    {
        $groupStmt = $this->db->prepare(
            'SELECT pg.id_grupo, pg.minimo_selecciones, pg.maximo_selecciones,
                    pg.selecciones_incluidas, pg.recargo_seleccion_extra
             FROM producto_personalizacion_grupos pg WHERE pg.id_producto = ?'
        );
        $groupStmt->execute([$productId]);
        $groups = [];
        foreach ($groupStmt->fetchAll(PDO::FETCH_ASSOC) as $group) {
            $groups[(int) $group['id_grupo']] = [
                'min' => (int) $group['minimo_selecciones'],
                'max' => (int) $group['maximo_selecciones'],
                'included' => (int) $group['selecciones_incluidas'],
                'extra' => $group['recargo_seleccion_extra'],
                'count' => 0,
            ];
        }

        $optionStmt = $this->db->prepare(
            "SELECT o.id_opcion, o.id_grupo, o.nombre AS opcion_nombre, g.nombre AS grupo_nombre, po.recargo
             FROM producto_personalizacion_opciones po
             JOIN personalizacion_opciones o ON o.id_opcion = po.id_opcion
             JOIN personalizacion_grupos g ON g.id_grupo = o.id_grupo
             WHERE po.id_producto = ? AND po.estado = 'Activo'
               AND o.estado = 'Activo' AND g.estado = 'Activo'"
        );
        $optionStmt->execute([$productId]);
        $available = [];
        foreach ($optionStmt->fetchAll(PDO::FETCH_ASSOC) as $option) {
            $available[(int) $option['id_opcion']] = $option;
        }

        $selectedIds = [];
        foreach ($selected as $rawId) {
            $id = filter_var($rawId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($id === false || isset($selectedIds[$id])) {
                throw new InvalidArgumentException('La seleccion de opciones no es valida.');
            }
            $selectedIds[$id] = true;
        }

        $priced = [];
        $surcharge = 0.0;
        foreach (array_keys($selectedIds) as $optionId) {
            if (!isset($available[$optionId])) {
                throw new InvalidArgumentException('Una opcion seleccionada no pertenece al producto.');
            }
            $option = $available[$optionId];
            $groupId = (int) $option['id_grupo'];
            if (!isset($groups[$groupId])) {
                throw new InvalidArgumentException('La opcion seleccionada no tiene un grupo valido.');
            }
            $groups[$groupId]['count']++;
            $optionSurcharge = Validator::catalogAmount($option['recargo'], 'el recargo de ' . $option['opcion_nombre']);
            if ($groups[$groupId]['count'] > $groups[$groupId]['included']) {
                $optionSurcharge += Validator::catalogAmount($groups[$groupId]['extra'], 'el recargo por selección adicional');
            }
            $surcharge = round($surcharge + $optionSurcharge, 2);
            $priced[] = [
                'id_opcion' => $optionId,
                'grupo_nombre' => (string) $option['grupo_nombre'],
                'opcion_nombre' => (string) $option['opcion_nombre'],
                'recargo' => $optionSurcharge,
            ];
        }

        foreach ($groups as $limits) {
            if ($limits['count'] < $limits['min'] || $limits['count'] > $limits['max']) {
                throw new InvalidArgumentException('La seleccion de personalizaciones no cumple las reglas del producto.');
            }
        }

        return ['surcharge' => $surcharge, 'options' => $priced];
    }
}
