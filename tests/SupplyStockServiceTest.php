<?php
declare(strict_types=1);

use App\Services\SupplyStockService;
use PHPUnit\Framework\TestCase;

final class SupplyStockServiceTest extends TestCase
{
    private PDO $db;
    private SupplyStockService $service;

    protected function setUp(): void
    {
        $this->db = new PDO('sqlite::memory:');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->db->exec('CREATE TABLE insumos (id_insumo INTEGER PRIMARY KEY, nombre TEXT, estado TEXT)');
        $this->db->exec('CREATE TABLE producto_insumos (id_producto INTEGER, id_insumo INTEGER, cantidad NUMERIC)');
        $this->db->exec('CREATE TABLE inventario_insumos (id_inventario_insumo INTEGER PRIMARY KEY, id_sucursal INTEGER, id_insumo INTEGER, stock_actual NUMERIC)');
        $this->db->exec('CREATE TABLE movimientos_insumos (id_inventario_insumo INTEGER, id_usuario INTEGER, tipo TEXT, cantidad NUMERIC, stock_anterior NUMERIC, stock_posterior NUMERIC, referencia_tipo TEXT, referencia_id INTEGER)');
        $this->db->exec("INSERT INTO insumos VALUES (1, 'Vaso 8 oz', 'Activo'), (2, 'Vaso batido', 'Activo')");
        $this->db->exec('INSERT INTO producto_insumos VALUES (10, 1, 1), (11, 1, 1), (20, 2, 1)');
        $this->db->exec('INSERT INTO inventario_insumos VALUES (100, 3, 1, 10), (200, 3, 2, 4)');
        $this->service = new SupplyStockService($this->db);
    }

    public function testAggregatesDifferentDrinksThatUseTheSameCup(): void
    {
        $allocations = $this->service->lockForSale([10 => 3, 11 => 2], 3);

        self::assertCount(1, $allocations);
        self::assertSame(5.0, $allocations[0]['quantity']);
        self::assertSame(5.0, $allocations[0]['stock_after']);
    }

    public function testDeductsSupplyAndCreatesMovement(): void
    {
        $allocations = $this->service->lockForSale([20 => 2], 3);
        $this->service->deductForSale($allocations, 7, 55);

        self::assertSame(2.0, (float) $this->db->query('SELECT stock_actual FROM inventario_insumos WHERE id_inventario_insumo = 200')->fetchColumn());
        self::assertSame(-2.0, (float) $this->db->query('SELECT cantidad FROM movimientos_insumos')->fetchColumn());
    }

    public function testRejectsSharedCupOverselling(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Stock insuficiente de Vaso 8 oz');
        $this->service->lockForSale([10 => 8, 11 => 4], 3);
    }
}
