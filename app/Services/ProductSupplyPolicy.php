<?php
namespace App\Services;

use InvalidArgumentException;

final class ProductSupplyPolicy
{
    public static function assertCompatible(string $productType, string $supplyUsage): void
    {
        if (!in_array($productType, ['bebida', 'batido'], true) || $productType !== $supplyUsage) {
            throw new InvalidArgumentException('El vaso seleccionado no corresponde al tipo de producto.');
        }
    }
}
