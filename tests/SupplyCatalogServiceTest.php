<?php
declare(strict_types=1);

use App\Services\SupplyCatalogService;
use PHPUnit\Framework\TestCase;

final class SupplyCatalogServiceTest extends TestCase
{
    private PDO $db;
    private SupplyCatalogService $service;

    protected function setUp(): void
    {
        $this->db = new PDO('sqlite::memory:');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->db->exec('PRAGMA foreign_keys = ON');
        $this->db->exec("CREATE TABLE sucursales (id_sucursal INTEGER PRIMARY KEY, estado TEXT)");
        $this->db->exec("CREATE TABLE insumos (
            id_insumo INTEGER PRIMARY KEY AUTOINCREMENT, codigo TEXT UNIQUE, nombre TEXT UNIQUE,
            unidad_medida TEXT, tipo_uso TEXT, stock_minimo NUMERIC, estado TEXT
        )");
        $this->db->exec("CREATE TABLE inventario_insumos (
            id_inventario_insumo INTEGER PRIMARY KEY AUTOINCREMENT, id_sucursal INTEGER, id_insumo INTEGER,
            stock_actual NUMERIC, stock_minimo NUMERIC,
            FOREIGN KEY (id_insumo) REFERENCES insumos(id_insumo) ON DELETE CASCADE
        )");
        $this->db->exec('CREATE TABLE productos (id_producto INTEGER PRIMARY KEY, tipo_producto TEXT, estado TEXT)');
        $this->db->exec('CREATE TABLE producto_insumos (id_producto INTEGER, id_insumo INTEGER)');
        $this->db->exec('CREATE TABLE movimientos_insumos (id_inventario_insumo INTEGER)');
        $this->db->exec('CREATE TABLE auditoria (
            id_usuario INTEGER, accion TEXT, entidad TEXT, entidad_id INTEGER,
            id_sucursal INTEGER, detalles TEXT, direccion_ip TEXT
        )');
        $this->db->exec("INSERT INTO sucursales VALUES (1, 'Activa'), (2, 'Activa'), (3, 'Inactiva')");
        $_SESSION['id_usuario'] = 1;
        $this->service = new SupplyCatalogService($this->db);
    }

    public function testCreatesCupAndZeroStockForEveryActiveBranch(): void
    {
        $cup = $this->service->create(['nombre' => 'Vaso 20 oz', 'tipo_uso' => 'bebida', 'stock_minimo' => 12]);

        self::assertSame('cup_vaso_20_oz', $cup['codigo']);
        self::assertSame(2, (int) $this->db->query('SELECT COUNT(*) FROM inventario_insumos')->fetchColumn());
        self::assertSame(12.0, (float) $this->db->query('SELECT MIN(stock_minimo) FROM inventario_insumos')->fetchColumn());
    }

    public function testUpdatesCupAndMinimumStock(): void
    {
        $cup = $this->service->create(['nombre' => 'Vaso 20 oz', 'tipo_uso' => 'bebida', 'stock_minimo' => 10]);
        $updated = $this->service->update($cup['id_insumo'], ['nombre' => 'Vaso 22 oz', 'tipo_uso' => 'bebida', 'stock_minimo' => 15]);

        self::assertSame('Vaso 22 oz', $updated['nombre']);
        self::assertSame(15, $updated['stock_minimo']);
        self::assertSame('Vaso 22 oz', $this->db->query('SELECT nombre FROM insumos')->fetchColumn());
        self::assertSame(15.0, (float) $this->db->query('SELECT MIN(stock_minimo) FROM inventario_insumos')->fetchColumn());
    }

    public function testRejectsDeactivationWhenActiveProductUsesCup(): void
    {
        $cup = $this->service->create(['nombre' => 'Vaso 20 oz', 'tipo_uso' => 'bebida', 'stock_minimo' => 10]);
        $this->db->exec("INSERT INTO productos VALUES (8, 'bebida', 'Activo')");
        $this->db->prepare('INSERT INTO producto_insumos VALUES (8, ?)')->execute([$cup['id_insumo']]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('productos activos');
        $this->service->setStatus($cup['id_insumo'], 'Inactivo');
    }

    public function testDeletesCupThatHasNeverBeenUsed(): void
    {
        $cup = $this->service->create(['nombre' => 'Vaso temporal', 'tipo_uso' => 'bebida', 'stock_minimo' => 10]);
        $this->service->delete($cup['id_insumo']);

        self::assertSame(0, (int) $this->db->query('SELECT COUNT(*) FROM insumos')->fetchColumn());
        self::assertSame(0, (int) $this->db->query('SELECT COUNT(*) FROM inventario_insumos')->fetchColumn());
    }
}
