<?php
declare(strict_types=1);

use App\Services\ProductOptionPricingService;
use PHPUnit\Framework\TestCase;

final class ProductOptionPricingServiceTest extends TestCase
{
    private ProductOptionPricingService $service;

    protected function setUp(): void
    {
        $db = new PDO('sqlite::memory:');
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->exec('CREATE TABLE producto_personalizacion_grupos (id_producto INTEGER, id_grupo INTEGER, minimo_selecciones INTEGER, maximo_selecciones INTEGER, selecciones_incluidas INTEGER, recargo_seleccion_extra NUMERIC)');
        $db->exec('CREATE TABLE personalizacion_grupos (id_grupo INTEGER PRIMARY KEY, nombre TEXT, estado TEXT)');
        $db->exec('CREATE TABLE personalizacion_opciones (id_opcion INTEGER PRIMARY KEY, id_grupo INTEGER, nombre TEXT, estado TEXT)');
        $db->exec('CREATE TABLE producto_personalizacion_opciones (id_producto INTEGER, id_opcion INTEGER, recargo NUMERIC, estado TEXT)');
        $db->exec("INSERT INTO personalizacion_grupos VALUES (1, 'Fruta', 'Activo'), (2, 'Leche', 'Activo'), (3, 'Leche para bebida', 'Activo'), (4, 'Saborizante', 'Activo')");
        $db->exec('INSERT INTO producto_personalizacion_grupos VALUES (8, 1, 1, 3, 1, 15), (8, 2, 1, 1, 1, 0), (9, 3, 1, 1, 1, 0), (9, 4, 1, 1, 1, 0)');
        $db->exec("INSERT INTO personalizacion_opciones VALUES (10, 1, 'Fresa', 'Activo'), (11, 1, 'Mango', 'Activo'), (12, 1, 'Banano', 'Activo'), (20, 2, 'Entera', 'Activo'), (21, 2, 'Deslactosada', 'Activo'), (30, 3, 'Entera', 'Activo'), (31, 3, 'Deslactosada', 'Activo'), (40, 4, 'Sin saborizante', 'Activo'), (41, 4, 'Vainilla', 'Activo')");
        $db->exec("INSERT INTO producto_personalizacion_opciones VALUES (8, 10, 0, 'Activo'), (8, 11, 0, 'Activo'), (8, 12, 0, 'Activo'), (8, 20, 0, 'Activo'), (8, 21, 15, 'Activo'), (9, 30, 0, 'Activo'), (9, 31, 15, 'Activo'), (9, 40, 0, 'Activo'), (9, 41, 10, 'Activo')");
        $this->service = new ProductOptionPricingService($db);
    }

    public function testChargesEachFruitAfterTheFirstAndLactoseFreeMilk(): void
    {
        $result = $this->service->price(8, [10, 11, 12, 21]);

        self::assertSame(45.0, $result['surcharge']);
        self::assertCount(4, $result['options']);
        self::assertSame(15.0, $result['options'][1]['recargo']);
        self::assertSame(15.0, $result['options'][3]['recargo']);
    }

    public function testRequiresFruitAndMilk(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->price(8, [10]);
    }

    public function testRejectsMoreFruitsThanAllowed(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->price(8, [10, 11, 12, 20, 21]);
    }

    /** @dataProvider hotDrinkPrices */
    public function testPricesHotDrinkOptions(array $options, float $expectedSurcharge): void
    {
        $result = $this->service->price(9, $options);

        self::assertSame($expectedSurcharge, $result['surcharge']);
    }

    public static function hotDrinkPrices(): array
    {
        return [
            'entera sin saborizante' => [[30, 40], 0.0],
            'deslactosada sin saborizante' => [[31, 40], 15.0],
            'entera con saborizante' => [[30, 41], 10.0],
            'deslactosada con saborizante' => [[31, 41], 25.0],
        ];
    }
}
