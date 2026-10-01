<?php
use App\Services\ProductSupplyPolicy;
use PHPUnit\Framework\TestCase;

final class ProductSupplyPolicyTest extends TestCase
{
    /** @dataProvider compatibleSupplies */
    public function testAllowsCompatibleSupplies(string $productType, string $supplyUsage): void
    {
        ProductSupplyPolicy::assertCompatible($productType, $supplyUsage);
        self::assertTrue(true);
    }

    public static function compatibleSupplies(): array
    {
        return [
            'vaso para bebida' => ['bebida', 'bebida'],
            'vaso para batido' => ['batido', 'batido'],
        ];
    }

    public function testRejectsSupplyFromAnotherProductType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('El vaso seleccionado no corresponde al tipo de producto.');

        ProductSupplyPolicy::assertCompatible('bebida', 'batido');
    }
}
