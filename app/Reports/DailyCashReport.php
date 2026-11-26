<?php
declare(strict_types=1);

namespace App\Reports;

use PDO;

final class DailyCashReport
{
    public function __construct(private PDO $db) {}

    public function data(?array $branches, string $date, ?int $session = null): array
    {
        $cash = (new CashCategoryReport($this->db))->totals($branches, $date, $session);
        $cashCents = array_sum(array_column($cash, 'cobrado'));
        $methods = [];
        if ($session === null) {
            $methods = (new CashIncomeReport($this->db))->summarize(['desde' => $date, 'hasta' => $date], $branches)['by_method'];
            $methods['Efectivo'] = $cashCents / 100;
        }
        [$expenseScope, $expenseParams] = $this->scope('m.id_sucursal', $branches);
        $expenses = $this->rows(
            "SELECT COALESCE(NULLIF(m.descripcion, ''), m.motivo) AS nombre, m.monto AS valor
             FROM mermas_caja m WHERE $expenseScope AND " . ($session === null ? 'DATE(m.fecha_registro)' : 'm.id_caja') . ' = ?
             ORDER BY m.fecha_registro DESC',
            [...$expenseParams, $session ?? $date]
        );
        [$inventoryScope, $inventoryParams] = $this->scope('i.id_sucursal', $branches);
        $waste = $this->rows(
            "SELECT CONCAT(p.nombre_producto, ' • ', m.motivo) AS nombre, m.cantidad AS valor
             FROM mermas m JOIN inventario i ON i.id_inventario = m.id_inventario
             JOIN productos p ON p.id_producto = i.id_producto
             WHERE $inventoryScope AND DATE(m.fecha_registro) = ? ORDER BY m.fecha_registro DESC",
            [...$inventoryParams, $date]
        );
        $inventory = $this->rows(
            "SELECT p.nombre_producto AS nombre, COALESCE(c.nombre_categoria, 'Sin categoría') AS categoria,
                    SUM(i.stock_actual) AS valor
             FROM inventario i JOIN productos p ON p.id_producto = i.id_producto
             LEFT JOIN categorias c ON c.id_categoria = p.id_categoria WHERE $inventoryScope
             GROUP BY p.id_producto, p.nombre_producto, c.nombre_categoria
             HAVING SUM(i.stock_actual) > 0 ORDER BY categoria, nombre",
            $inventoryParams
        );
        [$orderScope, $orderParams] = $this->scope('o.id_sucursal', $branches);
        [$saleScope, $saleParams] = $this->scope('v.id_sucursal', $branches);
        $sales = $this->rows(
            "SELECT p.nombre_producto AS nombre, COALESCE(c.nombre_categoria, 'Sin categoría') AS categoria,
                    SUM(t.cantidad) AS unidades, SUM(t.subtotal) AS valor
             FROM (
                SELECT d.id_producto, d.cantidad, d.subtotal
                FROM pedidos o JOIN pedido_detalles d ON d.id_pedido = o.id_pedido
                WHERE $orderScope AND o.estado = 'Entregado' AND DATE(COALESCE(o.fecha_entregado, o.fecha_registro)) = ?
                UNION ALL
                SELECT d.id_producto, d.cantidad, d.subtotal
                FROM ventas_directas v JOIN venta_items d ON d.id_venta = v.id_venta
                WHERE $saleScope AND DATE(v.fecha_venta) = ?
             ) t JOIN productos p ON p.id_producto = t.id_producto
             LEFT JOIN categorias c ON c.id_categoria = p.id_categoria
             GROUP BY p.id_producto, p.nombre_producto, c.nombre_categoria ORDER BY categoria, nombre",
            [...$orderParams, $date, ...$saleParams, $date]
        );
        $expenseCents = array_sum(array_map(static fn (array $row): int => CashCategoryReport::cents($row['valor']), $expenses));
        $branchName = count($branches ?? []) === 1
            ? ($this->rows('SELECT nombre_sucursal FROM sucursales WHERE id_sucursal = ?', $branches)[0]['nombre_sucursal'] ?? null)
            : null;

        return [
            'fecha' => $date,
            'sucursal' => $branchName,
            'metodos' => $methods,
            'categorias' => $cash,
            'efectivo_centavos' => $cashCents,
            'gastos' => $expenses,
            'gastos_centavos' => $expenseCents,
            'esperado' => $cashCents - $expenseCents,
            'mermas' => $waste,
            'inventario' => $inventory,
            'ventas' => $sales,
        ];
    }

    private function scope(string $column, ?array $branches): array
    {
        if ($branches === null) return ['1 = 1', []];
        if ($branches === []) return ['1 = 0', []];
        return [$column . ' IN (' . implode(',', array_fill(0, count($branches), '?')) . ')', $branches];
    }

    private function rows(string $sql, array $params): array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
