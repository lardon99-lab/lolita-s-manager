<?php
declare(strict_types=1);

namespace App\Reports;

use PDO;

final class CashIncomeReport
{
    public function __construct(private PDO $db)
    {
    }

    /**
     * @param array<string, mixed> $filters
     * @param null|list<int> $allowedBranches Null means unrestricted access.
     * @return array{order_payments: float, direct_sales: float, total: float}
     */
    public function summarize(array $filters, ?array $allowedBranches): array
    {
        $orderPayments = $this->sumOrderPayments($filters, $allowedBranches);
        $directSales = $this->sumDirectSales($filters, $allowedBranches);

        return [
            'order_payments' => $orderPayments,
            'direct_sales' => $directSales,
            'total' => round($orderPayments + $directSales, 2),
        ];
    }

    /** @param array<string, mixed> $filters @param null|list<int> $allowedBranches */
    private function sumOrderPayments(array $filters, ?array $allowedBranches): float
    {
        if (($filters['tipo'] ?? null) === 'Venta') return 0.0;

        $conditions = ["pp.estado = 'Aplicado'"];
        $params = [];
        $this->addBranchScope($conditions, $params, 'p.id_sucursal', 'payment_branch', $filters, $allowedBranches);
        $this->addDateScope($conditions, $params, 'pp.fecha_registro', 'payment', $filters);

        if (!empty($filters['busqueda'])) {
            $conditions[] = '(c.nombre_completo LIKE :payment_search OR CAST(p.id_pedido AS CHAR) LIKE :payment_search_id)';
            $search = '%' . (string) $filters['busqueda'] . '%';
            $params[':payment_search'] = $search;
            $params[':payment_search_id'] = $search;
        }

        $sql = 'SELECT COALESCE(SUM(pp.monto), 0)
                FROM pagos_pedido pp
                INNER JOIN pedidos p ON p.id_pedido = pp.id_pedido
                INNER JOIN clientes c ON c.id_cliente = p.id_cliente
                WHERE ' . implode(' AND ', $conditions);

        return $this->sum($sql, $params);
    }

    /** @param array<string, mixed> $filters @param null|list<int> $allowedBranches */
    private function sumDirectSales(array $filters, ?array $allowedBranches): float
    {
        if (($filters['tipo'] ?? null) === 'Pedido') return 0.0;

        $conditions = ['1 = 1'];
        $params = [];
        $this->addBranchScope($conditions, $params, 'v.id_sucursal', 'sale_branch', $filters, $allowedBranches);
        $this->addDateScope($conditions, $params, 'v.fecha_venta', 'sale', $filters);

        if (!empty($filters['busqueda'])) {
            $conditions[] = "('Publico General' LIKE :sale_search OR CAST(v.id_venta AS CHAR) LIKE :sale_search_id)";
            $search = '%' . (string) $filters['busqueda'] . '%';
            $params[':sale_search'] = $search;
            $params[':sale_search_id'] = $search;
        }

        $sql = 'SELECT COALESCE(SUM(v.total), 0)
                FROM ventas_directas v
                WHERE ' . implode(' AND ', $conditions);

        return $this->sum($sql, $params);
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

        $conditions[] = 'DATE(' . $column . ') BETWEEN :' . $prefix . '_from AND :' . $prefix . '_to';
        $params[':' . $prefix . '_from'] = $filters['desde'];
        $params[':' . $prefix . '_to'] = $filters['hasta'];
    }

    /** @param array<string, mixed> $params */
    private function sum(string $sql, array $params): float
    {
        $statement = $this->db->prepare($sql);
        $statement->execute($params);
        return round((float) $statement->fetchColumn(), 2);
    }
}
