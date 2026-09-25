<?php
declare(strict_types=1);

use App\Services\BeverageCustomizationFactory;
use PHPUnit\Framework\TestCase;

final class BeverageCustomizationFactoryTest extends TestCase
{
    public function testBuildsMilkAndFlavoringGroupsWithBusinessSurcharges(): void
    {
        $groups = BeverageCustomizationFactory::fromInput([
            'bebida_permite_leche' => '1',
            'bebida_recargo_deslactosada' => '15.00',
            'bebida_permite_saborizante' => '1',
            'bebida_saborizantes' => "Vainilla\nCaramelo",
            'bebida_recargo_saborizante' => '10.00',
        ]);

        self::assertCount(2, $groups);
        self::assertSame('drink_milk', $groups[0]['codigo']);
        self::assertSame(15.0, $groups[0]['opciones'][1]['recargo']);
        self::assertSame('Sin saborizante', $groups[1]['opciones'][0]['nombre']);
        self::assertTrue($groups[1]['opciones'][0]['predeterminada']);
        self::assertSame(10.0, $groups[1]['opciones'][1]['recargo']);
    }

    public function testReturnsNoGroupsWhenPersonalizationIsDisabled(): void
    {
        self::assertSame([], BeverageCustomizationFactory::fromInput([]));
    }

    public function testRejectsRepeatedFlavoringsIgnoringCase(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No puedes repetir un saborizante.');

        BeverageCustomizationFactory::fromInput([
            'bebida_permite_saborizante' => '1',
            'bebida_saborizantes' => "Vainilla\nVAINILLA",
        ]);
    }

    public function testRejectsEmptyFlavoringCatalogWhenEnabled(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Agrega entre 1 y 20 saborizantes.');

        BeverageCustomizationFactory::fromInput([
            'bebida_permite_saborizante' => '1',
            'bebida_saborizantes' => '',
        ]);
    }
}
