<?php
declare(strict_types=1);

namespace App\Services;

use App\Security\Auth;
use PDO;

final class DashboardService
{
    public function __construct(private PDO $db) {}

    public function data(): array
    {
        [$scopeSql, $scopeParams] = $this->scope('id_sucursal');
        [$pedidoScope, $pedidoParams] = $this->scope('p.id_sucursal');
        [$ventaScope, $ventaParams] = $this->scope('v.id_sucursal');
        $today = date('Y-m-d');
        $limit = date('Y-m-d', strtotime('+7 days'));

        $pending = $this->scalar("SELECT COUNT(*) FROM pedidos p WHERE p.estado = 'Pendiente'{$pedidoScope}", $pedidoParams);
        $todayOrders = $this->scalar("SELECT COUNT(*) FROM pedidos p WHERE DATE(p.fecha_entrega) = :today AND p.estado != 'Entregado'{$pedidoScope}", [':today' => $today] + $pedidoParams);
        $delivered = $this->scalar("SELECT COALESCE(SUM(p.total_pedido), 0) FROM pedidos p WHERE p.estado = 'Entregado' AND DATE(p.fecha_registro) = :today{$pedidoScope}", [':today' => $today] + $pedidoParams);
        $direct = $this->scalar("SELECT COALESCE(SUM(v.total), 0) FROM ventas_directas v WHERE DATE(v.fecha_venta) = :today{$ventaScope}", [':today' => $today] + $ventaParams);

        [$inventoryScope, $inventoryParams] = $this->scope('i.id_sucursal');
        $stockSql = "SELECT p.id_producto, i.id_sucursal, MIN(i.id_inventario) AS id_inventario, p.nombre_producto,
                            SUM(i.stock_actual) AS stock_actual, MAX(i.stock_minimo) AS stock_minimo, s.nombre_sucursal
                     FROM inventario i JOIN productos p ON i.id_producto = p.id_producto
                     JOIN sucursales s ON i.id_sucursal = s.id_sucursal
                     WHERE 1=1{$inventoryScope}
                     GROUP BY p.id_producto, i.id_sucursal, p.nombre_producto, s.nombre_sucursal
                     HAVING SUM(i.stock_actual) <= MAX(i.stock_minimo)
                     ORDER BY stock_actual ASC";
        $stockStmt = $this->db->prepare($stockSql);
        $stockStmt->execute($inventoryParams);

        $expired = $this->expiry("i.fecha_caducidad <= :today", [':today' => $today], $inventoryScope, $inventoryParams);
        $expiring = $this->expiry("i.fecha_caducidad BETWEEN DATE_ADD(:today, INTERVAL 1 DAY) AND :limit", [':today' => $today, ':limit' => $limit], $inventoryScope, $inventoryParams);

        return [
            'metricas' => ['pendientes' => (int) $pending, 'para_hoy' => (int) $todayOrders, 'ventas' => (float) $delivered + (float) $direct],
            'alertas' => $stockStmt->fetchAll(),
            'productos_caducados' => $expired,
            'productos_por_caducar' => $expiring,
        ];
    }

    private function scalar(string $sql, array $params): mixed
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    private function expiry(string $dateCondition, array $dateParams, string $scopeSql, array $scopeParams): array
    {
        $sql = "SELECT i.id_inventario, i.id_producto, i.id_sucursal, p.nombre_producto, s.nombre_sucursal, i.fecha_caducidad, i.stock_actual
                FROM inventario i JOIN productos p ON i.id_producto = p.id_producto
                JOIN sucursales s ON i.id_sucursal = s.id_sucursal
                WHERE i.fecha_caducidad IS NOT NULL AND {$dateCondition} AND i.stock_actual > 0{$scopeSql}
                ORDER BY i.fecha_caducidad ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($dateParams + $scopeParams);
        return $stmt->fetchAll();
    }

    private function scope(string $column): array
    {
        $allowed = Auth::allowedBranches();
        if ($allowed === null) return ['', []];
        if ($allowed === []) return [' AND 1 = 0', []];
        $holders = [];
        $params = [];
        foreach ($allowed as $index => $branchId) {
            $key = ':scope_' . preg_replace('/\W+/', '_', $column) . '_' . $index;
            $holders[] = $key;
            $params[$key] = $branchId;
        }
        return [' AND ' . $column . ' IN (' . implode(',', $holders) . ')', $params];
    }
}
