<?php
declare(strict_types=1);

use App\Http\Input\ClientInput;
use App\Http\Input\ProductInput;
use App\Http\Input\ReportFilters;
use PHPUnit\Framework\TestCase;

final class InputValidationTest extends TestCase
{
    public function testClientInputNormalizesOptionalContactFields(): void
    {
        $input = ClientInput::from(['nombre' => ' Ana ', 'telefono' => '', 'email' => '']);
        self::assertSame('Ana', $input['name']);
        self::assertNull($input['phone']);
        self::assertNull($input['email']);
    }

    public function testProductRequiresExactlyOneCategorySource(): void
    {
        $this->expectException(InvalidArgumentException::class);
        ProductInput::create([
            'id_categoria' => '1',
            'nueva_categoria_nombre' => 'Nueva',
            'nombre_producto' => 'Producto',
            'precio_base' => '10',
            'id_sucursal' => ['1'],
        ]);
    }

    public function testReportFiltersUseStrictBranchAndDateRange(): void
    {
        $filters = ReportFilters::from([
            'desde' => '2026-09-01',
            'hasta' => '2026-09-24',
            'sucursal' => '2',
            'tipo' => 'Venta',
        ], '2026-09-24');
        self::assertSame(2, $filters['sucursal']);
        self::assertSame('Venta', $filters['tipo']);
    }
}
