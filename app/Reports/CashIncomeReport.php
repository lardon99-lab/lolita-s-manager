<?php
declare(strict_types=1);

namespace App\Reports;

use PDO;

final class CashIncomeReport
{
    public const METHODS = ['Efectivo', 'Tarjeta', 'Transferencia', 'Otro'];
    public const CARD_PAGE_SIZE = 25;

    public function __construct(private PDO $db)
    {
    }

    /**
     * @param array<string, mixed> $filters
     * @param null|list<int> $allowedBranches Null means unrestricted access.
     * @return array{order_payments:float,direct_sales:float,total:float,by_method:array<string,float>}
     */
    public function summarize(array $filters, ?array $allowedBranches): array
    {
        [$sources, $params] = $this->incomeSources($filters, $allowedBranches);
        $statement = $this->db->prepare(
            "SELECT origen, metodo_pago, SUM(monto) AS total
             FROM ($sources) ingresos GROUP BY origen, metodo_pago"
        );
        $statement->execute($params);

        $byMethod = array_fill_keys(self::METHODS, 0.0);
        $bySource = ['Pedido' => 0.0, 'Venta' => 0.0];
        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $amount = (float) $row['total'];
            $bySource[$row['origen']] += $amount;
            $method = $row['metodo_pago'];
            $byMethod[isset($byMethod[$method]) ? $method : 'Otro'] += $amount;
        }
        return [
            'order_payments' => round($bySource['Pedido'], 2),
            'direct_sales' => round($bySource['Venta'], 2),
            'total' => round(array_sum($bySource), 2),
            'by_method' => array_map(static fn (float $amount): float => round($amount, 2), $byMethod),
        ];
    }

    public function cardPayments(array $filters, ?array $allowedBranches, int $page): array
    {
        [$sources, $params] = $this->incomeSources($filters, $allowedBranches);
        $statement = $this->db->prepare(
            "SELECT i.origen, i.id_operacion, i.monto, i.fecha, s.nombre_sucursal,
                    COALESCE(NULLIF(u.nombre_real, ''), u.nombre_usuario) AS operador,
                    COALESCE(cv.terminal, cp.terminal) AS terminal,
                    COALESCE(cv.fecha_operacion, cp.fecha_operacion) AS fecha_comprobante,
                    COALESCE(cv.referencia, cp.referencia) AS referencia
             FROM ($sources) i
             JOIN sucursales s ON s.id_sucursal = i.id_sucursal
             JOIN usuarios u ON u.id_usuario = i.id_usuario
             LEFT JOIN comprobantes_tarjeta cv ON cv.id_venta = i.id_venta
             LEFT JOIN comprobantes_tarjeta cp ON cp.id_pago = i.id_pago
             WHERE i.metodo_pago = 'Tarjeta'
             ORDER BY i.fecha DESC, i.origen, i.id_operacion DESC, i.id_pago DESC
             LIMIT :limit OFFSET :offset"
        );
        foreach ($params as $key => $value) $statement->bindValue($key, $value);
        $statement->bindValue(':limit', self::CARD_PAGE_SIZE + 1, PDO::PARAM_INT);
        $statement->bindValue(':offset', (max(1, $page) - 1) * self::CARD_PAGE_SIZE, PDO::PARAM_INT);
        $statement->execute();
        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);
        return [
            'rows' => array_slice($rows, 0, self::CARD_PAGE_SIZE),
            'has_next' => count($rows) > self::CARD_PAGE_SIZE,
        ];
    }

    private function incomeSources(array $filters, ?array $allowedBranches): array
    {
        $params = [];
        $sources = [];
        if (($filters['tipo'] ?? null) !== 'Venta') $sources[] = $this->orderPayments($filters, $allowedBranches, $params);
        if (($filters['tipo'] ?? null) !== 'Pedido') $sources[] = $this->directSales($filters, $allowedBranches, $params);
        return [implode(' UNION ALL ', $sources), $params];
    }

    private function orderPayments(array $filters, ?array $allowedBranches, array &$params): string
    {
        $conditions = ["pp.estado = 'Aplicado'"];
        $this->addBranchScope($conditions, $params, 'p.id_sucursal', 'payment_branch', $filters, $allowedBranches);
        $this->addDateScope($conditions, $params, 'pp.fecha_registro', 'payment', $filters);

        if (!empty($filters['busqueda'])) {
            $conditions[] = '(c.nombre_completo LIKE :payment_search OR CAST(p.id_pedido AS CHAR) LIKE :payment_search_id)';
            $search = '%' . (string) $filters['busqueda'] . '%';
            $params[':payment_search'] = $search;
            $params[':payment_search_id'] = $search;
        }

        return "SELECT 'Pedido' AS origen, p.id_pedido AS id_operacion, pp.id_pago, NULL AS id_venta,
                       p.id_sucursal, pp.id_usuario, pp.fecha_registro AS fecha, pp.metodo_pago, pp.monto
                FROM pagos_pedido pp
                INNER JOIN pedidos p ON p.id_pedido = pp.id_pedido
                INNER JOIN clientes c ON c.id_cliente = p.id_cliente
                WHERE " . implode(' AND ', $conditions);
    }

    private function directSales(array $filters, ?array $allowedBranches, array &$params): string
    {
        $conditions = ['1 = 1'];
        $this->addBranchScope($conditions, $params, 'v.id_sucursal', 'sale_branch', $filters, $allowedBranches);
        $this->addDateScope($conditions, $params, 'v.fecha_venta', 'sale', $filters);

        if (!empty($filters['busqueda'])) {
            $conditions[] = "('Publico General' LIKE :sale_search OR CAST(v.id_venta AS CHAR) LIKE :sale_search_id)";
            $search = '%' . (string) $filters['busqueda'] . '%';
            $params[':sale_search'] = $search;
            $params[':sale_search_id'] = $search;
        }

        return "SELECT 'Venta' AS origen, v.id_venta AS id_operacion, NULL AS id_pago, v.id_venta,
                       v.id_sucursal, v.id_usuario, v.fecha_venta AS fecha, v.metodo_pago, v.total AS monto
                FROM ventas_directas v
                WHERE " . implode(' AND ', $conditions);
    }

    /**
     * @param list<string> $conditions
     * @param array<string, mixed> $params
     * @param array<string, mixed> $filters
     * @param null|list<int> $allowedBranches
     */
    private function addBranchScope(
        array &$conditions,
        array &$params,
        string $column,
        string $prefix,
        array $filters,
        ?array $allowedBranches
    ): void {
        if (!empty($filters['sucursal'])) {
            if ($allowedBranches !== null && !in_array((int) $filters['sucursal'], $allowedBranches, true)) {
                $conditions[] = '1 = 0';
                return;
            }
            $conditions[] = $column . ' = :' . $prefix;
            $params[':' . $prefix] = (int) $filters['sucursal'];
            return;
        }

        if ($allowedBranches === null) return;
        if ($allowedBranches === []) {
            $conditions[] = '1 = 0';
            return;
        }

        $holders = [];
        foreach ($allowedBranches as $index => $branchId) {
            $key = ':' . $prefix . '_' . $index;
            $holders[] = $key;
            $params[$key] = $branchId;
        }
        $conditions[] = $column . ' IN (' . implode(',', $holders) . ')';
    }

    /** @param list<string> $conditions @param array<string, mixed> $params @param array<string, mixed> $filters */
    private function addDateScope(array &$conditions, array &$params, string $column, string $prefix, array $filters): void
    {
        if (empty($filters['desde']) || empty($filters['hasta'])) return;

        $conditions[] = $column . ' >= :' . $prefix . '_from AND ' . $column . ' < :' . $prefix . '_to';
        $params[':' . $prefix . '_from'] = $filters['desde'];
        $params[':' . $prefix . '_to'] = (new \DateTimeImmutable($filters['hasta']))->modify('+1 day')->format('Y-m-d');
    }
}
