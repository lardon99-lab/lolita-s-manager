<?php
declare(strict_types=1);

use App\Services\InventoryRestockService;
use PHPUnit\Framework\TestCase;

final class InventoryRestockServiceTest extends TestCase
{
    private PDO $db;
    private InventoryRestockService $service;
    private string $expiry;

    protected function setUp(): void
    {
        $_SESSION['id_usuario'] = 9;
        $this->db = new PDO('sqlite::memory:');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->db->exec('CREATE TABLE productos (id_producto INTEGER PRIMARY KEY, nombre_producto TEXT, estado TEXT, dias_vida_util INTEGER)');
        $this->db->exec('CREATE TABLE inventario (id_inventario INTEGER PRIMARY KEY AUTOINCREMENT, id_sucursal INTEGER, id_producto INTEGER, stock_actual INTEGER, stock_minimo INTEGER, fecha_caducidad TEXT)');
        $this->db->exec('CREATE TABLE abastecimientos (id_abastecimiento INTEGER PRIMARY KEY AUTOINCREMENT, id_sucursal INTEGER, id_usuario INTEGER, idempotency_key TEXT, total_productos INTEGER DEFAULT 0, total_unidades INTEGER DEFAULT 0, observaciones TEXT, fecha_registro TEXT DEFAULT CURRENT_TIMESTAMP, UNIQUE(id_usuario, idempotency_key))');
        $this->db->exec('CREATE TABLE movimientos_inventario (id_inventario INTEGER, id_usuario INTEGER, tipo TEXT, cantidad INTEGER, stock_anterior INTEGER, stock_posterior INTEGER, referencia_tipo TEXT, referencia_id INTEGER)');
        $this->db->exec('CREATE TABLE auditoria (id_usuario INTEGER, accion TEXT, entidad TEXT, entidad_id INTEGER, id_sucursal INTEGER, detalles TEXT, direccion_ip TEXT)');
        $this->expiry = date('Y-m-d', strtotime('+30 days'));
        $this->db->exec("INSERT INTO productos VALUES (1, 'Cafe', 'Activo', 0), (2, 'Pastel', 'Activo', 5), (3, 'Inactivo', 'Inactivo', 0)");
        $insert = $this->db->prepare('INSERT INTO inventario (id_sucursal, id_producto, stock_actual, stock_minimo, fecha_caducidad) VALUES (?, ?, ?, ?, ?)');
        $insert->execute([1, 1, 3, 5, null]);
        $insert->execute([1, 2, 2, 5, $this->expiry]);
        $insert->execute([1, 3, 4, 5, null]);
        $this->service = new InventoryRestockService($this->db);
    }

    public function testUpdatesExistingLotAndRecordsReceiptAndMovement(): void
    {
        $result = $this->service->restock(1, 9, [
            ['product_id' => 2, 'quantity' => 4, 'expiry' => $this->expiry],
        ], 'receipt-one');

        self::assertFalse($result['duplicate']);
        self::assertSame(4, $result['units']);
        self::assertSame(6, (int) $this->db->query('SELECT stock_actual FROM inventario WHERE id_producto = 2')->fetchColumn());
        self::assertSame('abastecimientos', $this->db->query('SELECT referencia_tipo FROM movimientos_inventario')->fetchColumn());
    }

    public function testCreatesDifferentExpiryLot(): void
    {
        $this->service->restock(1, 9, [
            ['product_id' => 2, 'quantity' => 3, 'expiry' => date('Y-m-d', strtotime('+31 days'))],
        ], 'receipt-two');

        self::assertSame(2, (int) $this->db->query('SELECT COUNT(*) FROM inventario WHERE id_producto = 2')->fetchColumn());
        $statement = $this->db->prepare('SELECT stock_actual FROM inventario WHERE fecha_caducidad = ?');
        $statement->execute([date('Y-m-d', strtotime('+31 days'))]);
        self::assertSame(3, (int) $statement->fetchColumn());
    }

    public function testSameIdempotencyKeyDoesNotDuplicateStock(): void
    {
        $items = [['product_id' => 1, 'quantity' => 7, 'expiry' => null]];
        $first = $this->service->restock(1, 9, $items, 'receipt-three');
        $second = $this->service->restock(1, 9, $items, 'receipt-three');

        self::assertFalse($first['duplicate']);
        self::assertTrue($second['duplicate']);
        self::assertSame(10, (int) $this->db->query('SELECT stock_actual FROM inventario WHERE id_producto = 1')->fetchColumn());
        self::assertSame(1, (int) $this->db->query('SELECT COUNT(*) FROM movimientos_inventario')->fetchColumn());
    }

    public function testRollsBackWholeReceiptWhenOneProductIsInactive(): void
    {
        try {
            $this->service->restock(1, 9, [
                ['product_id' => 1, 'quantity' => 2, 'expiry' => null],
                ['product_id' => 3, 'quantity' => 2, 'expiry' => null],
            ], 'receipt-four');
            self::fail('The inactive product should reject the receipt.');
        } catch (InvalidArgumentException $error) {
            self::assertStringContainsString('no esta activo', $error->getMessage());
        }

        self::assertSame(3, (int) $this->db->query('SELECT stock_actual FROM inventario WHERE id_producto = 1')->fetchColumn());
        self::assertSame(0, (int) $this->db->query('SELECT COUNT(*) FROM abastecimientos')->fetchColumn());
        self::assertSame(0, (int) $this->db->query('SELECT COUNT(*) FROM movimientos_inventario')->fetchColumn());
    }

    public function testRejectsPastExpiryDate(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->restock(1, 9, [
            ['product_id' => 2, 'quantity' => 1, 'expiry' => '2020-01-01'],
        ], 'receipt-five');
    }
}
