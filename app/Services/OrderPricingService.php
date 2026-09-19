<?php
declare(strict_types=1);

namespace App\Services;

use App\Http\Validator;
use InvalidArgumentException;
use PDO;

final class OrderPricingService
{
    public function __construct(private PDO $db) {}

    public function price(int $branchId, array $input): array
    {
        $lines = $this->normalizeLines($input);
        if ($lines === [] || count($lines) > 100) {
            throw new InvalidArgumentException('El pedido no contiene productos validos.');
        }

        $productQuery = $this->db->prepare(
            "SELECT p.precio_base
             FROM productos p
             JOIN inventario i ON i.id_producto = p.id_producto AND i.id_sucursal = ?
             WHERE p.id_producto = ? AND p.estado = 'Activo'
             LIMIT 1"
        );
        $groupQuery = $this->db->prepare(
            'SELECT id_grupo, minimo_selecciones, maximo_selecciones FROM producto_personalizacion_grupos WHERE id_producto = ?'
        );
        $optionQuery = $this->db->prepare(
            "SELECT po.id_opcion, po.recargo, o.id_grupo, o.nombre AS opcion_nombre, g.nombre AS grupo_nombre
             FROM producto_personalizacion_opciones po
             JOIN personalizacion_opciones o ON o.id_opcion = po.id_opcion
             JOIN personalizacion_grupos g ON g.id_grupo = o.id_grupo
             WHERE po.id_producto = ? AND po.estado = 'Activo'
               AND o.estado = 'Activo' AND g.estado = 'Activo'"
        );

        $items = [];
        $total = 0.0;
        foreach ($lines as $line) {
            $productId = Validator::positiveInt($line['producto'] ?? null, 'producto');
            $quantity = Validator::positiveInt($line['cantidad'] ?? null, 'cantidad');
            if ($quantity > 1000) throw new InvalidArgumentException('La cantidad excede el limite permitido.');
            $notes = Validator::text($line['personalizacion'] ?? '', 'personalizacion', 1000, false);

            $productQuery->execute([$branchId, $productId]);
            $basePrice = $productQuery->fetchColumn();
            if ($basePrice === false) throw new InvalidArgumentException('Uno de los productos no esta disponible en la sucursal seleccionada.');

            $groupQuery->execute([$productId]);
            $groups = [];
            foreach ($groupQuery->fetchAll(PDO::FETCH_ASSOC) as $group) {
                $groups[(int) $group['id_grupo']] = ['min' => (int) $group['minimo_selecciones'], 'max' => (int) $group['maximo_selecciones'], 'count' => 0];
            }
            $optionQuery->execute([$productId]);
            $available = [];
            foreach ($optionQuery->fetchAll(PDO::FETCH_ASSOC) as $option) $available[(int) $option['id_opcion']] = $option;

            $rawSelected = is_array($line['opciones'] ?? null) ? $line['opciones'] : [];
            $selectedIds = array_values(array_unique(array_map('intval', $rawSelected)));
            $pricedOptions = [];
            $surcharge = 0.0;
            foreach ($selectedIds as $optionId) {
                if ($optionId <= 0 || !isset($available[$optionId])) throw new InvalidArgumentException('Una opcion seleccionada no pertenece al producto.');
                $option = $available[$optionId];
                $groupId = (int) $option['id_grupo'];
                if (!isset($groups[$groupId])) throw new InvalidArgumentException('La opcion seleccionada no tiene un grupo valido.');
                $groups[$groupId]['count']++;
                $optionSurcharge = round((float) $option['recargo'], 2);
                $surcharge = round($surcharge + $optionSurcharge, 2);
                $pricedOptions[] = [
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

            $unitPrice = round((float) $basePrice + $surcharge, 2);
            $subtotal = round($unitPrice * $quantity, 2);
            $total = round($total + $subtotal, 2);
            $items[] = [
                'product_id' => $productId,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'notes' => $notes,
                'subtotal' => $subtotal,
                'options' => $pricedOptions,
            ];
        }

        if ($total <= 0 || $total > 99999999.99) throw new InvalidArgumentException('El total del pedido no es valido.');
        return ['items' => $items, 'total' => $total];
    }

    private function normalizeLines(array $input): array
    {
        if (is_array($input['lineas'] ?? null)) {
            return array_values(array_filter($input['lineas'], 'is_array'));
        }

        $products = is_array($input['productos'] ?? null) ? array_values($input['productos']) : [];
        $quantities = is_array($input['cantidades'] ?? null) ? array_values($input['cantidades']) : [];
        $customizations = is_array($input['personalizacion'] ?? null) ? array_values($input['personalizacion']) : [];
        $selections = is_array($input['opciones'] ?? null) ? $input['opciones'] : [];
        if (count($products) !== count($quantities)) return [];
        $lines = [];
        foreach ($products as $index => $product) {
            $lines[] = [
                'producto' => $product,
                'cantidad' => $quantities[$index] ?? null,
                'personalizacion' => $customizations[$index] ?? '',
                'opciones' => is_array($selections[$index] ?? null) ? $selections[$index] : [],
            ];
        }
        return $lines;
    }
}
