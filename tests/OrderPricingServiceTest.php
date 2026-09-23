<?php
declare(strict_types=1);

use App\Services\OrderPricingService;
use PHPUnit\Framework\TestCase;

final class OrderPricingServiceTest extends TestCase
{
    private PDO $db;
    private OrderPricingService $service;

    protected function setUp(): void
    {
        $this->db = new PDO('sqlite::memory:');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->db->exec("CREATE TABLE productos (id_producto INTEGER PRIMARY KEY, precio_base NUMERIC, tipo_producto TEXT, estado TEXT)");
        $this->db->exec("CREATE TABLE inventario (id_producto INTEGER, id_sucursal INTEGER)");
        $this->db->exec("CREATE TABLE producto_personalizacion_grupos (id_producto INTEGER, id_grupo INTEGER, minimo_selecciones INTEGER, maximo_selecciones INTEGER)");
        $this->db->exec("CREATE TABLE personalizacion_grupos (id_grupo INTEGER PRIMARY KEY, nombre TEXT, estado TEXT)");
        $this->db->exec("CREATE TABLE personalizacion_opciones (id_opcion INTEGER PRIMARY KEY, id_grupo INTEGER, nombre TEXT, estado TEXT)");
        $this->db->exec("CREATE TABLE producto_personalizacion_opciones (id_producto INTEGER, id_opcion INTEGER, recargo NUMERIC, estado TEXT)");
        $this->db->exec("CREATE TABLE producto_diseno_config (id_producto INTEGER PRIMARY KEY, permite_diseno INTEGER, permite_imagen INTEGER, recargo_diseno NUMERIC)");
        $this->db->exec("INSERT INTO productos VALUES (1, 365, 'pastel', 'Activo'), (2, 220, 'panaderia', 'Activo')");
        $this->db->exec("INSERT INTO inventario VALUES (1, 2), (2, 3)");
        $this->db->exec("INSERT INTO personalizacion_grupos VALUES (10, 'Relleno', 'Activo')");
        $this->db->exec("INSERT INTO producto_personalizacion_grupos VALUES (1, 10, 1, 1)");
        $this->db->exec("INSERT INTO personalizacion_opciones VALUES (100, 10, 'Jalea de pina', 'Activo'), (101, 10, 'Dulce de leche', 'Activo')");
        $this->db->exec("INSERT INTO producto_personalizacion_opciones VALUES (1, 100, 0, 'Activo'), (1, 101, 52, 'Activo')");
        $this->db->exec("INSERT INTO producto_diseno_config VALUES (1, 1, 1, 75)");
        $this->service = new OrderPricingService($this->db);
    }

    public function testCalculatesPriceFromPersistedSurcharge(): void
    {
        $result = $this->service->price(2, ['lineas' => [[
            'producto' => 1,
            'cantidad' => 2,
            'personalizacion' => 'Feliz cumpleanos',
            'opciones' => [101],
        ]]]);

        self::assertSame(834.0, $result['total']);
        self::assertSame(417.0, $result['items'][0]['unit_price']);
        self::assertSame('Dulce de leche', $result['items'][0]['options'][0]['opcion_nombre']);
    }

    public function testRejectsAnOptionNotAssignedToTheProduct(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->price(2, ['lineas' => [[
            'producto' => 1,
            'cantidad' => 1,
            'opciones' => [999],
        ]]]);
    }

    public function testRejectsAProductFromAnotherBranch(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->price(2, ['lineas' => [[
            'producto' => 2,
            'cantidad' => 1,
            'opciones' => [],
        ]]]);
    }

    public function testAddsPersistedCustomDesignSurcharge(): void
    {
        $result = $this->service->price(2, ['lineas' => [[
            'producto' => 1,
            'cantidad' => 1,
            'opciones' => [100],
            'diseno' => [
                'activo' => 1,
                'color' => 'Rosa y dorado',
                'frase' => 'Feliz cumpleanos',
            ],
        ]]]);

        self::assertSame(440.0, $result['total']);
        self::assertTrue($result['items'][0]['design']['enabled']);
        self::assertSame('Rosa y dorado', $result['items'][0]['design']['color']);
    }

    public function testRejectsDesignForProductWithoutPolicy(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->price(3, ['lineas' => [[
            'producto' => 2,
            'cantidad' => 1,
            'diseno' => ['activo' => 1],
        ]]]);
    }

    public function testAllowsCakeMessageWithoutCustomDesign(): void
    {
        $result = $this->service->price(2, ['lineas' => [[
            'producto' => 1,
            'cantidad' => 1,
            'opciones' => [100],
            'diseno' => ['frase' => 'Feliz cumpleanos Pepe'],
        ]]]);

        self::assertSame(365.0, $result['total']);
        self::assertFalse($result['items'][0]['design']['enabled']);
        self::assertSame('Feliz cumpleanos Pepe', $result['items'][0]['design']['phrase']);
    }

    public function testAllowsMultipleCakeColorsWithoutSurcharge(): void
    {
        $result = $this->service->price(2, ['lineas' => [[
            'producto' => 1,
            'cantidad' => 1,
            'opciones' => [100],
            'diseno' => ['color' => 'Celeste, Rosado, Amarillo'],
        ]]]);

        self::assertSame(365.0, $result['total']);
        self::assertFalse($result['items'][0]['design']['enabled']);
        self::assertSame('Celeste, Rosado, Amarillo', $result['items'][0]['design']['color']);
        self::assertSame(0.0, $result['items'][0]['design']['surcharge']);
    }

    public function testRejectsColorsForANonCakeProduct(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->price(3, ['lineas' => [[
            'producto' => 2,
            'cantidad' => 1,
            'diseno' => ['color' => 'Rosado'],
        ]]]);
    }
}
