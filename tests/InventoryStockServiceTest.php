<?php
declare(strict_types=1);

use App\Services\InventoryStockService;
use PHPUnit\Framework\TestCase;

final class InventoryStockServiceTest extends TestCase
{
    private PDO $db;
    private InventoryStockService $service;

    protected function setUp(): void
    {
        $this->db = new PDO('sqlite::memory:');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->db->exec("CREATE TABLE productos (id_producto INTEGER PRIMARY KEY, nombre_producto TEXT, precio_base NUMERIC, estado TEXT)");
        $this->db->exec("CREATE TABLE inventario (id_inventario INTEGER PRIMARY KEY, id_producto INTEGER, id_sucursal INTEGER, stock_actual INTEGER, fecha_caducidad TEXT)");
        $this->db->exec("CREATE TABLE movimientos_inventario (id_inventario INTEGER, id_usuario INTEGER, tipo TEXT, cantidad INTEGER, stock_anterior INTEGER, stock_posterior INTEGER, referencia_tipo TEXT, referencia_id INTEGER)");
        $this->db->exec("INSERT INTO productos VALUES (20, 'Pastel normal', 365, 'Activo')");
        $this->db->exec("INSERT INTO inventario VALUES (1, 20, 1, 0, NULL), (2, 20, 1, 3, date('now', '+1 day')), (3, 20, 1, 7, date('now', '+2 day'))");
        $this->service = new InventoryStockService($this->db);
    }

    public function testAllocatesAcrossAvailableLotsAndIgnoresEmptyRows(): void
    {
        $result = $this->service->lockForSale(20, 1, 5);

        self::assertSame(365.0, $result['price']);
        self::assertSame(2, count($result['allocations']));
        self::assertSame(3, $result['allocations'][0]['quantity']);
        self::assertSame(2, $result['allocations'][1]['quantity']);
    }

    public function testDeductsEveryAllocationAndRecordsMovements(): void
    {
        $result = $this->service->lockForSale(20, 1, 5);
        $this->service->deductForSale($result['allocations'], 9, 100);

        $stocks = array_map('intval', $this->db->query('SELECT stock_actual FROM inventario ORDER BY id_inventario')->fetchAll(PDO::FETCH_COLUMN));
        self::assertSame([0, 0, 5], $stocks);
        self::assertSame(2, (int) $this->db->query('SELECT COUNT(*) FROM movimientos_inventario')->fetchColumn());
    }

    public function testRejectsQuantityAboveCombinedStock(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->lockForSale(20, 1, 11);
    }
}
