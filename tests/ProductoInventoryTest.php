<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__) . '/app/models/Producto.php';

final class ProductoInventoryTest extends TestCase
{
    private PDO $db;
    private Producto $model;

    protected function setUp(): void
    {
        $this->db = new PDO('sqlite::memory:');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->db->sqliteCreateFunction('FLOOR', static fn ($value): float => floor((float) $value), 1);
        $this->db->exec('CREATE TABLE categorias (id_categoria INTEGER PRIMARY KEY, nombre_categoria TEXT)');
        $this->db->exec('CREATE TABLE sucursales (id_sucursal INTEGER PRIMARY KEY, nombre_sucursal TEXT)');
        $this->db->exec('CREATE TABLE productos (
            id_producto INTEGER PRIMARY KEY, id_categoria INTEGER, nombre_producto TEXT, precio_base NUMERIC,
            dias_vida_util INTEGER, estado TEXT, tipo_producto TEXT, control_inventario TEXT
        )');
        $this->db->exec('CREATE TABLE producto_sucursales (id_producto INTEGER, id_sucursal INTEGER, estado TEXT)');
        $this->db->exec('CREATE TABLE inventario (
            id_inventario INTEGER PRIMARY KEY, id_producto INTEGER, id_sucursal INTEGER,
            stock_actual INTEGER, stock_minimo INTEGER, fecha_caducidad TEXT
        )');
        $this->db->exec('CREATE TABLE producto_insumos (id_producto INTEGER, id_insumo INTEGER, cantidad NUMERIC)');
        $this->db->exec('CREATE TABLE inventario_insumos (
            id_inventario_insumo INTEGER PRIMARY KEY, id_sucursal INTEGER, id_insumo INTEGER,
            stock_actual NUMERIC, stock_minimo NUMERIC
        )');

        $this->db->exec("INSERT INTO categorias VALUES (1, 'Panaderia'), (2, 'Bebidas')");
        $this->db->exec("INSERT INTO sucursales VALUES (1, 'Sucursal No1.')");
        $this->db->exec("INSERT INTO productos VALUES
            (1, 1, 'Pan blanco', 20, 2, 'Activo', 'panaderia', 'producto'),
            (2, 2, 'Capuchino 12 oz', 65, 0, 'Activo', 'bebida', 'insumos')");
        $this->db->exec("INSERT INTO producto_sucursales VALUES (1, 1, 'Activo'), (2, 1, 'Activo')");
        $this->db->exec("INSERT INTO inventario VALUES
            (1, 1, 1, 7, 3, NULL),
            (2, 1, 1, 4, 3, '2020-01-01')");
        $this->db->exec('INSERT INTO producto_insumos VALUES (2, 10, 1)');
        $this->db->exec('INSERT INTO inventario_insumos VALUES (1, 1, 10, 12, 5)');

        $this->model = new Producto($this->db);
    }

    public function testListsProductsAndSupplyControlledBeveragesTogether(): void
    {
        $products = $this->model->obtenerPorSucursal(1);
        $byId = [];
        foreach ($products as $product) $byId[(int) $product['id_producto']] = $product;

        self::assertCount(2, $products);
        self::assertSame(7, (int) $byId[1]['stock_actual']);
        self::assertSame(4, (int) $byId[1]['stock_vencido']);
        self::assertSame('insumos', $byId[2]['control_inventario']);
        self::assertSame(12, (int) $byId[2]['stock_actual']);
        self::assertSame(5, (int) $byId[2]['stock_minimo']);
    }

    public function testListsSupplyProductsInGlobalAndAllowedBranchQueries(): void
    {
        self::assertCount(2, $this->model->obtenerTodoElInventario());
        self::assertCount(2, $this->model->obtenerPorSucursales([1]));
        self::assertSame([], $this->model->obtenerPorSucursales([]));
    }
}
