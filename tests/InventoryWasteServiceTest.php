<?php
declare(strict_types=1);

use App\Services\InventoryWasteService;
use PHPUnit\Framework\TestCase;

final class InventoryWasteServiceTest extends TestCase
{
    private PDO $db;
    private InventoryWasteService $service;

    protected function setUp(): void
    {
        $this->db = new PDO('sqlite::memory:');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->db->exec('CREATE TABLE inventario (id_inventario INTEGER PRIMARY KEY, id_producto INTEGER, id_sucursal INTEGER, stock_actual INTEGER, fecha_caducidad TEXT)');
        $this->db->exec('CREATE TABLE mermas (id_inventario INTEGER, id_usuario INTEGER, cantidad INTEGER, motivo TEXT)');
        $this->db->exec('CREATE TABLE movimientos_inventario (id_inventario INTEGER, id_usuario INTEGER, tipo TEXT, cantidad INTEGER, stock_anterior INTEGER, stock_posterior INTEGER, motivo TEXT)');
        $this->db->exec("INSERT INTO inventario VALUES
            (1, 20, 2, 3, '2026-09-20'),
            (2, 20, 2, 5, '2026-09-21'),
            (3, 20, 3, 9, '2026-09-20')");
        $this->service = new InventoryWasteService($this->db);
    }

    public function testWasteIsAllocatedAcrossLotsAndFullyRecorded(): void
    {
        $stock = $this->service->lockProductStock(20, 2, 6);
        $this->service->deduct($stock['allocations'], 7, 'Producto danado');

        $remaining = array_map('intval', $this->db->query('SELECT stock_actual FROM inventario ORDER BY id_inventario')->fetchAll(PDO::FETCH_COLUMN));
        self::assertSame([0, 2, 9], $remaining);
        self::assertSame(6, (int) $this->db->query('SELECT SUM(cantidad) FROM mermas')->fetchColumn());
        self::assertSame(-6, (int) $this->db->query('SELECT SUM(cantidad) FROM movimientos_inventario')->fetchColumn());
        self::assertSame(2, (int) $this->db->query('SELECT COUNT(*) FROM mermas')->fetchColumn());
    }

    public function testWasteCannotExceedCombinedBranchStock(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->lockProductStock(20, 2, 9);
    }
}
