<?php
use App\Services\ProductSupplyPolicy;
use PHPUnit\Framework\TestCase;

final class ProductSupplyPolicyTest extends TestCase
{
    /** @dataProvider compatibleSupplies */
    public function testAllowsCompatibleSupplies(string $productType, string $supplyCode): void
    {
        ProductSupplyPolicy::assertCompatible($productType, $supplyCode);
        self::assertTrue(true);
    }

    public static function compatibleSupplies(): array
    {
        return [
            'bebida de 8 oz' => ['bebida', 'cup_8oz'],
            'bebida de 12 oz' => ['bebida', 'cup_12oz'],
            'bebida de 16 oz' => ['bebida', 'cup_16oz'],
            'batido estandar' => ['batido', 'cup_shake'],
        ];
    }

    public function testRejectsSupplyFromAnotherProductType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('El vaso seleccionado no corresponde al tipo de producto.');

        ProductSupplyPolicy::assertCompatible('bebida', 'cup_shake');
    }
}
