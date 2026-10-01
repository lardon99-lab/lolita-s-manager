<?php

use App\Security\Auth;

trait ReporteCajaPdfTrait
{
    public function descargarReportePDF($filtros = []) {
        Auth::requirePermission('reports.view');
        $today = date('Y-m-d');
        $filtros['desde'] = $today;
        $filtros['hasta'] = $today;
        $reportBranches = Auth::allowedBranches('reports.view');
        // 1. Obtener exclusivamente los movimientos del dia actual.
        $resumenIngresos = $this->obtenerResumenIngresosCaja($filtros);
        $totalIngresos = $resumenIngresos['total'];

        $mermasCaja = $this->obtenerMermas($filtros);
        $mermasInventario = $this->obtenerMermasInventario($filtros);
        $totalGastos = array_sum(array_column($mermasCaja, 'monto'));

        $totalCajaReal = $totalIngresos - $totalGastos;

        // ====================================================================
        // 2. LÓGICA: PREPARAR DATOS PARA LAS 3 COLUMNAS
        // ====================================================================
        
        // A. Separar salidas de caja de mermas de producto
        $gastos = [];
        $mermas_producto = [];

        foreach ($mermasCaja as $m) {
            $gastos[] = [
                'nombre' => !empty($m['descripcion']) ? $m['descripcion'] : $m['motivo'],
                'valor' => 'L. ' . number_format((float)$m['monto'], 2),
            ];
        }

        foreach ($mermasInventario as $m) {
            $mermas_producto[] = [
                'nombre' => $m['nombre_producto'] . ' • ' . $m['motivo'],
                'valor' => (int)$m['cantidad'] . ' und.'
            ];
        }

        // B. Obtener Inventario Restante filtrado por la sucursal seleccionada
        $inventario_restante = [];
        try {
            $queryInv = "SELECT p.nombre_producto as nombre, SUM(i.stock_actual) as valor
                         FROM productos p
                         INNER JOIN inventario i ON p.id_producto = i.id_producto";
            $paramsInv = [];

            if (!empty($filtros['sucursal'])) {
                $queryInv .= " WHERE i.id_sucursal = :sucursal";
                $paramsInv[':sucursal'] = $filtros['sucursal'];
            } elseif ($reportBranches !== null) {
                if ($reportBranches === []) return;
                $holders = [];
                foreach ($reportBranches as $index => $branchId) {
                    $key = ':pdf_inventory_branch_' . $index;
                    $holders[] = $key;
                    $paramsInv[$key] = $branchId;
                }
                $queryInv .= ' WHERE i.id_sucursal IN (' . implode(',', $holders) . ')';
            }

            $queryInv .= " GROUP BY p.id_producto, p.nombre_producto HAVING SUM(i.stock_actual) > 0 ORDER BY p.nombre_producto ASC";
            $stmtInv = $this->db->prepare($queryInv);
            $stmtInv->execute($paramsInv);
            $inventario_restante = $stmtInv->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            // Ignorar si falla, mostrará vacío
        }

        // C. Obtener Ventas Agrupadas por Producto
        $cond_p = "p.estado = 'Entregado'";
        $cond_v = "1=1";
        $params_agrup = [];

        $id_rol = (int)($_SESSION['id_rol'] ?? 0);
        if ($id_rol === 2) {
            $cond_p .= " AND p.id_sucursal = :s1";
            $cond_v .= " AND v.id_sucursal = :s2";
            $params_agrup[':s1'] = $params_agrup[':s2'] = (int)($_SESSION['id_sucursal'] ?? 0);
        } else if (!empty($filtros['sucursal'])) {
            $cond_p .= " AND p.id_sucursal = :s1";
            $cond_v .= " AND v.id_sucursal = :s2";
            $params_agrup[':s1'] = $params_agrup[':s2'] = $filtros['sucursal'];
        } elseif ($reportBranches !== null) {
            if ($reportBranches === []) return;
            $pedidoHolders = [];
            $ventaHolders = [];
            foreach ($reportBranches as $index => $branchId) {
                $pedidoKey = ':pdf_order_branch_' . $index;
                $ventaKey = ':pdf_sale_branch_' . $index;
                $pedidoHolders[] = $pedidoKey;
                $ventaHolders[] = $ventaKey;
                $params_agrup[$pedidoKey] = $branchId;
                $params_agrup[$ventaKey] = $branchId;
            }
            $cond_p .= ' AND p.id_sucursal IN (' . implode(',', $pedidoHolders) . ')';
            $cond_v .= ' AND v.id_sucursal IN (' . implode(',', $ventaHolders) . ')';
        }

        if (!empty($filtros['desde']) && !empty($filtros['hasta'])) {
            $cond_p .= " AND DATE(p.fecha_registro) BETWEEN :d1 AND :h1";
            $cond_v .= " AND DATE(v.fecha_venta) BETWEEN :d2 AND :h2";
            $params_agrup[':d1'] = $params_agrup[':d2'] = $filtros['desde'];
            $params_agrup[':h1'] = $params_agrup[':h2'] = $filtros['hasta'];
        }

        $sqlVentasAgrupadas = "
            SELECT nombre_producto as nombre, SUM(total_producto) as valor FROM (
                SELECT pr.nombre_producto, det.subtotal as total_producto
                FROM pedidos p
                INNER JOIN pedido_detalles det ON p.id_pedido = det.id_pedido
                INNER JOIN productos pr ON det.id_producto = pr.id_producto
                WHERE $cond_p
                UNION ALL
                SELECT pr.nombre_producto, vi.subtotal as total_producto
                FROM ventas_directas v
                INNER JOIN venta_items vi ON v.id_venta = vi.id_venta
                INNER JOIN productos pr ON vi.id_producto = pr.id_producto
                WHERE $cond_v
            ) as consolidados
            GROUP BY nombre_producto
            ORDER BY valor DESC
        ";
        
        try {
            $stmtVentas = $this->db->prepare($sqlVentasAgrupadas);
            $stmtVentas->execute($params_agrup);
            $ventas_agrupadas = $stmtVentas->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $ventas_agrupadas = [];
        }


        // ====================================================================
        // 3. CONSTRUCCIÓN DEL PDF - REDISEÑO ESTÉTICO
        // ====================================================================
        while (ob_get_level()) { ob_end_clean(); }
        ob_start();
        ?>
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
            <style>
                @page { margin: 24px 28px 30px; }
                body { font-family: 'Helvetica', Arial, sans-serif; font-size: 10px; color: #333; margin: 0; padding: 0; }
                .header { text-align: center; margin-bottom: 16px; padding-bottom: 10px; border-bottom: 3px solid #D81B60; }
                .titulo { font-size: 20px; font-weight: bold; color: #D81B60; text-transform: uppercase; margin-bottom: 5px; }
                .subtitulo { font-size: 12px; color: #555; }
                .card { width: 100%; border-collapse: collapse; table-layout: fixed; margin: 0 0 14px; page-break-inside: auto; }
                .card col:first-child { width: 75%; }
                .card col:last-child { width: 25%; }
                .card thead { display: table-header-group; }
                .card tr { page-break-inside: avoid; page-break-after: auto; }
                .card thead th { 
                    background-color: #FCE4EC;
                    color: #AD1457;
                    padding: 7px 9px;
                    text-align: left; 
                    font-size: 11px; 
                    font-weight: bold; 
                    text-transform: uppercase; 
                    border-bottom: 2px solid #F48FB1; 
                }
                .card tbody td { 
                    padding: 5px 9px;
                    border-bottom: 1px solid #eeeeee; 
                    color: #444;
                    overflow-wrap: break-word;
                }
                .card tbody tr:nth-child(even) { background-color: #fafafa; }
                .card tbody tr:last-child td { border-bottom: 1px solid #cccccc; }
                
                .text-right { text-align: right; font-weight: bold; color: #222; }
                .card thead th.text-right,
                .card tbody td.text-right { text-align: right; }
                .text-muted { color: #888; font-style: italic; }
                .summary { width: 100%; margin-bottom: 16px; border-collapse: collapse; background: #FFF0F5; border: 2px solid #D81B60; }
                .summary td { width: 33.33%; padding: 10px; text-align: center; border-right: 1px solid #F48FB1; }
                .summary td:last-child { border-right: 0; }
                .summary-label { display: block; margin-bottom: 3px; color: #880E4F; font-size: 9px; font-weight: bold; text-transform: uppercase; }
                .summary-value { font-size: 15px; font-weight: bold; color: #222; }
                .summary-value.net { color: #2E7D32; }
            </style>
        </head>
        <body>
            <div class="header">
                <div class="titulo">Reporte de Ventas y Caja - Lolita's Manager</div>
                <div class="subtitulo">
                    Resumen diario de Inventario, Gastos y Ventas del <strong><?= date('d/m/Y') ?></strong><br>
                    Generado el: <strong><?= date('d/m/Y h:i A') ?></strong>
                </div>
            </div>

            <table class="summary">
                <tr>
                    <td><span class="summary-label">Cobrado</span><span class="summary-value">L. <?= number_format($totalIngresos, 2) ?></span></td>
                    <td><span class="summary-label">Gastos</span><span class="summary-value">L. <?= number_format($totalGastos, 2) ?></span></td>
                    <td><span class="summary-label">Neto</span><span class="summary-value net">L. <?= number_format($totalCajaReal, 2) ?></span></td>
                </tr>
            </table>

            <table class="card">
                <colgroup><col width="75%"><col width="25%"></colgroup>
                <thead><tr><th>Resumen de ventas</th><th class="text-right">Total</th></tr></thead>
                <tbody>
                    <?php if (empty($ventas_agrupadas)): ?><tr><td colspan="2" class="text-muted">Sin ventas registradas.</td></tr>
                    <?php else: foreach ($ventas_agrupadas as $venta): ?>
                    <tr><td><?= htmlspecialchars($venta['nombre']) ?></td><td class="text-right">L. <?= number_format($venta['valor'], 2) ?></td></tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>

            <table class="card">
                <colgroup><col width="75%"><col width="25%"></colgroup>
                <thead><tr><th>Gastos operativos / salidas de caja</th><th class="text-right">Monto</th></tr></thead>
                <tbody>
                    <?php if (empty($gastos)): ?><tr><td colspan="2" class="text-muted">Sin salidas de caja registradas.</td></tr>
                    <?php else: foreach ($gastos as $gasto): ?>
                    <tr><td><?= htmlspecialchars($gasto['nombre']) ?></td><td class="text-right"><?= htmlspecialchars($gasto['valor']) ?></td></tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>

            <table class="card">
                <colgroup><col width="75%"><col width="25%"></colgroup>
                <thead><tr><th>Mermas de producto</th><th class="text-right">Cantidad</th></tr></thead>
                <tbody>
                    <?php if (empty($mermas_producto)): ?><tr><td colspan="2" class="text-muted">Sin mermas de producto registradas.</td></tr>
                    <?php else: foreach ($mermas_producto as $merma): ?>
                    <tr><td><?= htmlspecialchars($merma['nombre']) ?></td><td class="text-right"><?= htmlspecialchars($merma['valor']) ?></td></tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>

            <table class="card">
                <colgroup><col width="75%"><col width="25%"></colgroup>
                <thead><tr><th>Inventario restante</th><th class="text-right">Unidades</th></tr></thead>
                <tbody>
                    <?php if (empty($inventario_restante)): ?><tr><td colspan="2" class="text-muted">Sin datos de inventario.</td></tr>
                    <?php else: foreach ($inventario_restante as $item): ?>
                    <tr><td><?= htmlspecialchars($item['nombre']) ?></td><td class="text-right"><?= htmlspecialchars($item['valor']) ?></td></tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </body>
        </html>
        <?php
        $html = ob_get_clean();

        try {
            $options = new \Dompdf\Options();
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', true);
            
            $dompdf = new \Dompdf\Dompdf($options);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'landscape'); 
            $dompdf->render();
            
            $dompdf->stream("Reporte_Caja_".date('d_m_Y').".pdf", ["Attachment" => false]);
            exit;
        } catch (Exception $e) {
            \App\Support\Logger::error($e);
            die("No fue posible generar el PDF.");
        }
    }
}
