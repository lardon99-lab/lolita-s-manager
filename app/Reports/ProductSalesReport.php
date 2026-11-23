<?php
declare(strict_types=1);

namespace App\Reports;

use App\Security\Auth;
use PDO;

final class ProductSalesReport
{
    public function __construct(private PDO $db)
    {
    }

    /** @param array{desde:string,hasta:string,sucursal:?int,id_producto:?int} $filters */
    public function data(array $filters): array
    {
        Auth::requirePermission('reports.view');
        $allowedBranches = Auth::allowedBranches('reports.view');

        $branches = $this->branchOptions($allowedBranches);
        $products = $this->productOptions($allowedBranches);
        $hasAccess = $filters['sucursal'] === null
            || Auth::canAccessBranch($filters['sucursal'], 'reports.view');

        $rows = $hasAccess
            ? $this->salesRows($filters, $allowedBranches, $filters['id_producto'] !== null)
            : [];

        $units = array_sum(array_map(static fn (array $row): int => (int) $row['unidades'], $rows));
        $amount = array_sum(array_map(static fn (array $row): float => (float) $row['total_ventas'], $rows));
        $branchesWithSales = array_values(array_unique(array_map(
            static fn (array $row): int => (int) ($row['id_sucursal'] ?? 0),
            $rows
        )));

        return [
            'reporte_sucursales' => $branches,
            'reporte_productos' => $products,
            'reporte_filas' => $rows,
            'reporte_resumen' => [
                'unidades' => $units,
                'ventas' => round($amount, 2),
                'tercer_indicador' => $filters['id_producto'] === null
                    ? ['etiqueta' => 'Productos con ventas', 'valor' => count($rows)]
                    : ['etiqueta' => 'Sucursales con ventas', 'valor' => count(array_filter($branchesWithSales))],
            ],
            'reporte_acceso_denegado' => !$hasAccess,
        ];
    }

    /** @param null|list<int> $allowedBranches */
    private function branchOptions(?array $allowedBranches): array
    {
        if ($allowedBranches === []) return [];
        if ($allowedBranches === null) {
            return $this->db->query(
                "SELECT id_sucursal, nombre_sucursal FROM sucursales WHERE estado = 'Activa' ORDER BY nombre_sucursal"
            )->fetchAll(PDO::FETCH_ASSOC);
        }

        $holders = [];
        foreach ($allowedBranches as $index => $id) $holders[] = ':option_branch_' . $index;
        $stmt = $this->db->prepare(
            "SELECT id_sucursal, nombre_sucursal FROM sucursales
             WHERE estado = 'Activa' AND id_sucursal IN (" . implode(',', $holders) . ') ORDER BY nombre_sucursal'
        );
        foreach ($allowedBranches as $index => $id) $stmt->bindValue($holders[$index], $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @param null|list<int> $allowedBranches */
    private function productOptions(?array $allowedBranches): array
    {
        if ($allowedBranches === null) {
            return $this->db->query(
                'SELECT id_producto, nombre_producto, estado FROM productos ORDER BY nombre_producto'
            )->fetchAll(PDO::FETCH_ASSOC);
        }
        if ($allowedBranches === []) return [];

        $directHolders = [];
        $orderHolders = [];
        $params = [];
        foreach ($allowedBranches as $index => $branchId) {
            $directKey = ':products_direct_branch_' . $index;
            $orderKey = ':products_order_branch_' . $index;
            $directHolders[] = $directKey;
            $orderHolders[] = $orderKey;
            $params[$directKey] = $branchId;
            $params[$orderKey] = $branchId;
        }
        $sql = "SELECT DISTINCT product.id_producto, product.nombre_producto, product.estado
                FROM productos product
                INNER JOIN (
                    SELECT vi.id_producto
                    FROM ventas_directas v
                    INNER JOIN venta_items vi ON vi.id_venta = v.id_venta
                    WHERE v.id_sucursal IN (" . implode(',', $directHolders) . ")
                    UNION
                    SELECT detail.id_producto
                    FROM pedidos p
                    INNER JOIN pedido_detalles detail ON detail.id_pedido = p.id_pedido
                    WHERE p.estado = 'Entregado'
                      AND p.id_sucursal IN (" . implode(',', $orderHolders) . ")
                ) available_products ON available_products.id_producto = product.id_producto
                ORDER BY product.nombre_producto";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @param array{desde:string,hasta:string,sucursal:?int,id_producto:?int} $filters @param null|list<int> $allowedBranches */
    private function salesRows(array $filters, ?array $allowedBranches, bool $byBranch): array
    {
        if ($allowedBranches === []) return [];

        $params = [];
        $directWhere = [
            'v.fecha_venta >= :direct_from',
            'v.fecha_venta < :direct_until',
        ];
        $orderWhere = [
            "p.estado = 'Entregado'",
            'p.fecha_entregado >= :order_from',
            'p.fecha_entregado < :order_until',
        ];
        $until = (new \DateTimeImmutable($filters['hasta']))->modify('+1 day')->format('Y-m-d');
        $params[':direct_from'] = $params[':order_from'] = $filters['desde'];
        $params[':direct_until'] = $params[':order_until'] = $until;

        if ($filters['sucursal'] !== null) {
            $directWhere[] = 'v.id_sucursal = :selected_direct_branch';
            $orderWhere[] = 'p.id_sucursal = :selected_order_branch';
            $params[':selected_direct_branch'] = $params[':selected_order_branch'] = $filters['sucursal'];
        } elseif ($allowedBranches !== null) {
            $this->appendBranchScope($directWhere, $params, 'v.id_sucursal', 'direct_branch', $allowedBranches);
            $this->appendBranchScope($orderWhere, $params, 'p.id_sucursal', 'order_branch', $allowedBranches);
        }

        $grouping = $byBranch
            ? 'line.id_producto, product.nombre_producto, line.id_sucursal, branch.nombre_sucursal'
            : 'line.id_producto, product.nombre_producto';
        $branchColumns = $byBranch ? ', line.id_sucursal, branch.nombre_sucursal' : '';
        $branchJoin = $byBranch ? ' INNER JOIN sucursales branch ON branch.id_sucursal = line.id_sucursal' : '';
        $outerWhere = '';
        if ($filters['id_producto'] !== null) {
            $outerWhere = ' WHERE line.id_producto = :selected_product';
            $params[':selected_product'] = $filters['id_producto'];
        }

        $sql = "SELECT line.id_producto, product.nombre_producto" . $branchColumns . ",
                       SUM(line.cantidad) AS unidades,
                       SUM(line.subtotal) AS total_ventas,
                       COUNT(DISTINCT line.transaccion) AS transacciones
                FROM (
                    SELECT vi.id_producto, v.id_sucursal, vi.cantidad, vi.subtotal,
                           CONCAT('D-', v.id_venta) AS transaccion
                    FROM ventas_directas v
                    INNER JOIN venta_items vi ON vi.id_venta = v.id_venta
                    WHERE " . implode(' AND ', $directWhere) . "
                    UNION ALL
                    SELECT detail.id_producto, p.id_sucursal, detail.cantidad, detail.subtotal,
                           CONCAT('P-', p.id_pedido) AS transaccion
                    FROM pedidos p
                    INNER JOIN pedido_detalles detail ON detail.id_pedido = p.id_pedido
                    WHERE " . implode(' AND ', $orderWhere) . "
                ) line
                INNER JOIN productos product ON product.id_producto = line.id_producto
                {$branchJoin}
                {$outerWhere}
                GROUP BY {$grouping}
                ORDER BY unidades DESC, total_ventas DESC, product.nombre_producto ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @param list<string> $conditions @param array<string, mixed> $params @param list<int> $branches */
    private function appendBranchScope(array &$conditions, array &$params, string $column, string $prefix, array $branches): void
    {
        $holders = [];
        foreach ($branches as $index => $branchId) {
            $key = ':' . $prefix . '_' . $index;
            $holders[] = $key;
            $params[$key] = $branchId;
        }
        $conditions[] = $column . ' IN (' . implode(',', $holders) . ')';
    }
}
