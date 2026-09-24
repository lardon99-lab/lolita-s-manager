<?php
declare(strict_types=1);

namespace App\Http\Input;

use App\Http\Validator;
use InvalidArgumentException;

final class ProductInput
{
    public static function create(array $input): array
    {
        $categoryId = isset($input['id_categoria']) && $input['id_categoria'] !== ''
            ? Validator::positiveInt($input['id_categoria'], 'categoria')
            : null;
        $newCategory = isset($input['nueva_categoria_nombre']) && trim((string) $input['nueva_categoria_nombre']) !== ''
            ? Validator::text($input['nueva_categoria_nombre'], 'categoria nueva', 50)
            : null;
        if (($categoryId === null) === ($newCategory === null)) {
            throw new InvalidArgumentException('Selecciona una categoria existente o escribe una nueva, pero no ambas.');
        }

        $type = Validator::enum($input['tipo_producto'] ?? 'panaderia', ['pastel', 'panaderia', 'bebida', 'batido'], 'tipo de producto');
        return [
            'category_id' => $categoryId,
            'new_category' => $newCategory,
            'name' => Validator::text($input['nombre_producto'] ?? '', 'producto', 150),
            'price' => Validator::positiveMoney($input['precio_base'] ?? null, 'precio'),
            'type' => $type,
            'uses_supplies' => in_array($type, ['bebida', 'batido'], true),
            'description' => Validator::text($input['descripcion'] ?? '', 'descripcion', 2000, false),
            'branch_ids' => Validator::idList($input['id_sucursal'] ?? [], 'sucursales'),
            'initial_stock' => isset($input['stock_inicial']) && $input['stock_inicial'] !== ''
                ? Validator::intRange($input['stock_inicial'], 'stock inicial', 0, 100000) : 0,
            'shelf_life' => isset($input['dias_vida_util']) && $input['dias_vida_util'] !== ''
                ? Validator::intRange($input['dias_vida_util'], 'vida util', 0, 3650) : 0,
            'size' => Validator::text($input['tamano'] ?? '', 'tamano', 80, false),
            'cake_count' => Validator::intRange($input['cantidad_tortas'] ?? 1, 'cantidad de tortas', 1, 100),
            'notes' => Validator::text($input['observaciones'] ?? '', 'observaciones', 1000, false),
        ];
    }
}
