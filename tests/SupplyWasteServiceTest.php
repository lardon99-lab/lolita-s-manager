<?php
declare(strict_types=1);

use App\Services\SupplyWasteService;
use PHPUnit\Framework\TestCase;

final class SupplyWasteServiceTest extends TestCase
{
    private PDO $db;
    private SupplyWasteService $service;

    protected function setUp(): void
    {
        $this->db = new PDO('sqlite::memory:');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->db->exec('CREATE TABLE insumos (id_insumo INTEGER PRIMARY KEY, nombre TEXT, estado TEXT)');
        $this->db->exec('CREATE TABLE inventario_insumos (
            id_inventario_insumo INTEGER PRIMARY KEY, id_sucursal INTEGER, id_insumo INTEGER, stock_actual NUMERIC
        )');
        $this->db->exec('CREATE TABLE movimientos_insumos (
            id_inventario_insumo INTEGER, id_usuario INTEGER, tipo TEXT, cantidad NUMERIC,
            stock_anterior NUMERIC, stock_posterior NUMERIC, motivo TEXT
        )');
        $this->db->exec('CREATE TABLE auditoria (
            id_usuario INTEGER, accion TEXT, entidad TEXT, entidad_id INTEGER,
            id_sucursal INTEGER, detalles TEXT, direccion_ip TEXT
        )');
        $this->db->exec("INSERT INTO insumos VALUES (4, 'Vaso para granitas', 'Activo')");
        $this->db->exec('INSERT INTO inventario_insumos VALUES (40, 2, 4, 10)');
        $_SESSION['id_usuario'] = 7;
        $this->service = new SupplyWasteService($this->db);
    }

    public function testDeductsCupWasteAndRecordsMovementAndAudit(): void
    {
        $result = $this->service->record(2, 4, 7, 3, 'Daño/Rotura');

        self::assertSame(10.0, $result['stock_before']);
        self::assertSame(7.0, $result['stock_after']);
        self::assertSame(7.0, (float) $this->db->query('SELECT stock_actual FROM inventario_insumos')->fetchColumn());
        self::assertSame(-3.0, (float) $this->db->query('SELECT cantidad FROM movimientos_insumos')->fetchColumn());
        self::assertSame('Merma', $this->db->query('SELECT tipo FROM movimientos_insumos')->fetchColumn());
        self::assertSame(1, (int) $this->db->query('SELECT COUNT(*) FROM auditoria')->fetchColumn());
    }

    public function testRejectsWasteAboveAvailableStock(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Stock disponible: 10');

        $this->service->record(2, 4, 7, 11, 'Defecto');
    }

    public function testRejectsSupplyFromAnotherBranch(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('no esta disponible en la sucursal');

        $this->service->record(3, 4, 7, 1, 'Otro');
    }
}

