<?php
declare(strict_types=1);

namespace App\Services;

use App\Http\Validator;
use InvalidArgumentException;

final class BeverageCustomizationFactory
{
    public static function fromInput(array $input): array
    {
        $groups = [];

        if (!empty($input['bebida_permite_leche'])) {
            $lactoseFreeSurcharge = Validator::money(
                $input['bebida_recargo_deslactosada'] ?? 15,
                'recargo por leche deslactosada',
                1000000
            );
            $groups[] = [
                'codigo' => 'drink_milk',
                'nombre' => 'Leche para bebida',
                'obligatorio' => true,
                'maximo' => 1,
                'selecciones_incluidas' => 1,
                'recargo_seleccion_extra' => 0,
                'opciones' => [
                    ['nombre' => 'Entera', 'recargo' => 0, 'predeterminada' => true],
                    ['nombre' => 'Deslactosada', 'recargo' => $lactoseFreeSurcharge, 'predeterminada' => false],
                ],
            ];
        }

        if (!empty($input['bebida_permite_saborizante'])) {
            $flavorSurcharge = Validator::money(
                $input['bebida_recargo_saborizante'] ?? 10,
                'recargo por saborizante',
                1000000
            );
            $flavorLines = preg_split('/\R+/', (string) ($input['bebida_saborizantes'] ?? ''), -1, PREG_SPLIT_NO_EMPTY) ?: [];
            $flavors = [];
            foreach ($flavorLines as $flavor) {
                $name = Validator::singleLineText(trim($flavor), 'saborizante', 80);
                $key = mb_strtolower($name);
                if ($key === 'sin saborizante') {
                    throw new InvalidArgumentException('No agregues Sin saborizante; el sistema la incluye automaticamente.');
                }
                if (isset($flavors[$key])) {
                    throw new InvalidArgumentException('No puedes repetir un saborizante.');
                }
                $flavors[$key] = ['nombre' => $name, 'recargo' => $flavorSurcharge, 'predeterminada' => false];
            }
            if ($flavors === [] || count($flavors) > 20) {
                throw new InvalidArgumentException('Agrega entre 1 y 20 saborizantes.');
            }

            $groups[] = [
                'codigo' => 'drink_flavoring',
                'nombre' => 'Saborizante',
                'obligatorio' => true,
                'maximo' => 1,
                'selecciones_incluidas' => 1,
                'recargo_seleccion_extra' => 0,
                'opciones' => array_merge(
                    [['nombre' => 'Sin saborizante', 'recargo' => 0, 'predeterminada' => true]],
                    array_values($flavors)
                ),
            ];
        }

        return $groups;
    }
}
