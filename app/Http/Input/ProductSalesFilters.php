<?php
declare(strict_types=1);

namespace App\Http\Input;

use App\Http\Validator;
use InvalidArgumentException;

final class ProductSalesFilters
{
    /** @return array{desde:string,hasta:string,sucursal:?int,id_producto:?int} */
    public static function from(array $input, string $today): array
    {
        $monthStart = (new \DateTimeImmutable($today))->modify('first day of this month')->format('Y-m-d');
        [$from, $to] = Validator::dateRange(
            $input['desde'] ?? $monthStart,
            $input['hasta'] ?? $today
        );
        if (new \DateTimeImmutable($to) > new \DateTimeImmutable($today)) {
            throw new InvalidArgumentException('La fecha hasta no puede ser futura.');
        }

        return [
            'desde' => $from,
            'hasta' => $to,
            'sucursal' => isset($input['sucursal']) && $input['sucursal'] !== ''
                ? Validator::positiveInt($input['sucursal'], 'sucursal')
                : null,
            'id_producto' => isset($input['id_producto']) && $input['id_producto'] !== ''
                ? Validator::positiveInt($input['id_producto'], 'producto')
                : null,
        ];
    }
}
