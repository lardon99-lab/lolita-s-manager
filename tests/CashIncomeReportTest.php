<?php
declare(strict_types=1);

use App\Reports\CashIncomeReport;
use PHPUnit\Framework\TestCase;

final class CashIncomeReportTest extends TestCase
{
    private PDO $db;
    private CashIncomeReport $report;

    protected function setUp(): void
    {
        $this->db = new PDO('sqlite::memory:');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->db->exec('CREATE TABLE clientes (id_cliente INTEGER PRIMARY KEY, nombre_completo TEXT)');
        $this->db->exec('CREATE TABLE pedidos (id_pedido INTEGER PRIMARY KEY, id_cliente INTEGER, id_sucursal INTEGER)');
        $this->db->exec('CREATE TABLE pagos_pedido (id_pago INTEGER PRIMARY KEY, id_pedido INTEGER, monto NUMERIC, estado TEXT, fecha_registro TEXT)');
        $this->db->exec('CREATE TABLE ventas_directas (id_venta INTEGER PRIMARY KEY, id_sucursal INTEGER, total NUMERIC, fecha_venta TEXT)');

        $this->db->exec("INSERT INTO clientes VALUES (1, 'Cliente Uno'), (2, 'Cliente Dos')");
        $this->db->exec('INSERT INTO pedidos VALUES (10, 1, 1), (20, 2, 2)');
        $this->db->exec("INSERT INTO pagos_pedido VALUES
            (1, 10, 100, 'Aplicado', '2026-09-19 09:00:00'),
            (2, 10, 40, 'Aplicado', '2026-09-18 09:00:00'),
            (3, 10, 20, 'Anulado', '2026-09-19 10:00:00'),
            (4, 20, 80, 'Aplicado', '2026-09-19 11:00:00')");
        $this->db->exec("INSERT INTO ventas_directas VALUES
            (100, 1, 50, '2026-09-19 12:00:00'),
            (101, 1, 25, '2026-09-18 12:00:00'),
            (102, 2, 70, '2026-09-19 13:00:00')");

        $this->report = new CashIncomeReport($this->db);
    }

    public function testCountsOnlyAppliedPaymentsAndSalesInsideDateAndBranchScope(): void
    {
        $summary = $this->report->summarize(
            ['desde' => '2026-09-19', 'hasta' => '2026-09-19'],
            [1]
        );

        self::assertSame(100.0, $summary['order_payments']);
        self::assertSame(50.0, $summary['direct_sales']);
        self::assertSame(150.0, $summary['total']);
    }

    public function testTypeFilterSeparatesOrdersFromDirectSales(): void
    {
        $orders = $this->report->summarize(
            ['desde' => '2026-09-19', 'hasta' => '2026-09-19', 'tipo' => 'Pedido'],
            null
        );
        $sales = $this->report->summarize(
            ['desde' => '2026-09-19', 'hasta' => '2026-09-19', 'tipo' => 'Venta'],
            null
        );

        self::assertSame(180.0, $orders['total']);
        self::assertSame(120.0, $sales['total']);
    }
}
