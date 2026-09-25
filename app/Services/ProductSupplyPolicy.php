<?php
namespace App\Services;

use InvalidArgumentException;

final class ProductSupplyPolicy
{
    private const ALLOWED_CODES = [
        'bebida' => ['cup_8oz', 'cup_12oz', 'cup_16oz'],
        'batido' => ['cup_shake'],
    ];

    public static function assertCompatible(string $productType, string $supplyCode): void
    {
        $allowedCodes = self::ALLOWED_CODES[$productType] ?? [];
        if (!in_array($supplyCode, $allowedCodes, true)) {
            throw new InvalidArgumentException('El vaso seleccionado no corresponde al tipo de producto.');
        }
    }
}

