<?php
declare(strict_types=1);

namespace App\Http\Input;

use App\Http\Validator;

final class ReportFilters
{
    public static function from(array $input, string $defaultDate): array
    {
        [$from, $to] = Validator::dateRange(
            $input['desde'] ?? $defaultDate,
            $input['hasta'] ?? $defaultDate
        );

        return [
            'desde' => $from,
            'hasta' => $to,
            'busqueda' => isset($input['busqueda']) && trim((string) $input['busqueda']) !== ''
                ? Validator::text($input['busqueda'], 'busqueda', 100)
                : null,
            'sucursal' => isset($input['sucursal']) && $input['sucursal'] !== ''
                ? Validator::positiveInt($input['sucursal'], 'sucursal')
                : null,
            'tipo' => isset($input['tipo']) && $input['tipo'] !== ''
                ? Validator::enum($input['tipo'], ['Pedido', 'Venta'], 'tipo')
                : null,
        ];
    }
}
