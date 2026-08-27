<?php

use App\Security\Auth;

trait ReporteCajaPdfTrait
{
    public function descargarReportePDF($filtros = []) {
        // 1. Obtener datos de ventas y mermas generales
        $ventas = $this->obtenerHistorialVentas($filtros);
        $totalIngresos = array_sum(array_column($ventas, 'total_pedido'));

        $mermasCaja = $this->obtenerMermas($filtros);
        $mermasInventario = $this->obtenerMermasInventario($filtros);
        $totalMermas = array_sum(array_column($mermasCaja, 'monto'));

        $totalCajaReal = $totalIngresos - $totalMermas;

        date_default_timezone_set('America/Tegucigalpa');

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
                'nombre' => htmlspecialchars($m['nombre_producto']) . ' • ' . htmlspecialchars($m['motivo']),
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
        }

        if (!empty($filtros['desde']) && !empty($filtros['hasta'])) {
            $cond_p .= " AND DATE(p.fecha_registro) BETWEEN :d1 AND :h1";
            $cond_v .= " AND DATE(v.fecha_venta) BETWEEN :d2 AND :h2";
            $params_agrup[':d1'] = $params_agrup[':d2'] = $filtros['desde'];
            $params_agrup[':h1'] = $params_agrup[':h2'] = $filtros['hasta'];
        }

        $sqlVentasAgrupadas = "
            SELECT nombre_producto as nombre, SUM(total_producto) as valor FROM (
                SELECT pr.nombre_producto, (det.cantidad * pr.precio_base) as total_producto
                FROM pedidos p
                INNER JOIN pedido_detalles det ON p.id_pedido = det.id_pedido
                INNER JOIN productos pr ON det.id_producto = pr.id_producto
                WHERE $cond_p
                UNION ALL
                SELECT pr.nombre_producto, (vi.cantidad * pr.precio_base) as total_producto
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
                /* Paleta y tipografía principal */
                body { font-family: 'Helvetica', Arial, sans-serif; font-size: 11px; color: #333; margin: 0; padding: 0;}
                
                /* Encabezado Principal */
                .header { text-align: center; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 3px solid #D81B60; }
                .titulo { font-size: 22px; font-weight: bold; color: #D81B60; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 5px;}
                .subtitulo { font-size: 12px; color: #555; }
                
                /* Layout de 3 columnas (espaciado) */
                .layout-table { width: 100%; border-collapse: separate; border-spacing: 12px 0; }
                .layout-td { width: 33.33%; vertical-align: top; padding: 0; }
                
                /* Diseño de las tarjetas de datos (Tablas interiores) */
                .card { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                .card thead th { 
                    background-color: #FCE4EC; /* Rosa suave */
                    color: #AD1457; /* Magenta oscuro */
                    padding: 8px 10px; 
                    text-align: left; 
                    font-size: 11px; 
                    font-weight: bold; 
                    text-transform: uppercase; 
                    border-bottom: 2px solid #F48FB1; 
                }
                .card tbody td { 
                    padding: 6px 10px; 
                    border-bottom: 1px solid #eeeeee; 
                    font-size: 10px; 
                    color: #444; 
                }
                .card tbody tr:nth-child(even) { background-color: #fafafa; }
                .card tbody tr:last-child td { border-bottom: 1px solid #cccccc; }
                
                /* Utilidades */
                .text-right { text-align: right; font-weight: bold; color: #222; }
                .text-muted { color: #888; font-style: italic; }
                
                /* Caja de Total Resaltada */
                .total-card { 
                    width: 100%; 
                    border-collapse: collapse; 
                    margin-top: 15px; 
                    border: 2px solid #D81B60; 
                    background-color: #FFF0F5; 
                }
                .total-card th { 
                    padding: 8px; 
                    text-align: center; 
                    font-size: 11px; 
                    color: #880E4F; 
                    text-transform: uppercase; 
                    border-bottom: 1px dashed #F48FB1; 
                }
                .total-card td { padding: 12px; text-align: center; }
                .total-monto { font-size: 18px; font-weight: bold; color: #2E7D32; } /* Verde para dinero positivo */
            </style>
        </head>
        <body>
            <div class="header">
                <div class="titulo">Reporte de Ventas y Caja - Lolita's Manager</div>
                <div class="subtitulo">
                    Resumen consolidado de Inventario, Gastos y Ventas<br>
                    Generado el: <strong><?= date('d/m/Y h:i A') ?></strong>
                </div>
            </div>

            <table class="layout-table">
                <tr>
                    <td class="layout-td">
                        <table class="card">
                            <thead>
                                <tr><th colspan="2">Inventario Restante</th></tr>
                            </thead>
                            <tbody>
                                <?php if(empty($inventario_restante)): ?>
                                    <tr><td colspan="2" class="text-muted">Sin datos de inventario...</td></tr>
                                <?php else: ?>
                                    <?php foreach($inventario_restante as $item): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($item['nombre']) ?></td>
                                            <td class="text-right"><?= htmlspecialchars($item['valor']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </td>

                    <td class="layout-td">
                        <table class="card">
                            <thead>
                                <tr><th colspan="2">Gastos Operativos / Salidas de Caja</th></tr>
                            </thead>
                            <tbody>
                                <?php if(empty($gastos)): ?>
                                    <tr><td colspan="2" class="text-muted">Sin salidas de caja registradas...</td></tr>
                                <?php else: ?>
                                    <?php foreach($gastos as $gasto): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($gasto['nombre']) ?></td>
                                            <td class="text-right"><?= htmlspecialchars($gasto['valor']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>

                        <table class="card">
                            <thead>
                                <tr><th colspan="2">Mermas de Producto</th></tr>
                            </thead>
                            <tbody>
                                <?php if(empty($mermas_producto)): ?>
                                    <tr><td colspan="2" class="text-muted">Sin mermas de producto registradas...</td></tr>
                                <?php else: ?>
                                    <?php foreach($mermas_producto as $merma): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($merma['nombre']) ?></td>
                                            <td class="text-right"><?= htmlspecialchars($merma['valor']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </td>

                    <td class="layout-td">
                        <table class="card">
                            <thead>
                                <tr><th colspan="2">Resumen de Ventas</th></tr>
                            </thead>
                            <tbody>
                                <?php if(empty($ventas_agrupadas)): ?>
                                    <tr><td colspan="2" class="text-muted">Sin ventas registradas...</td></tr>
                                <?php else: ?>
                                    <?php foreach($ventas_agrupadas as $venta): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($venta['nombre']) ?></td>
                                            <td class="text-right">L. <?= number_format($venta['valor'], 2) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>

                        <table class="total-card">
                            <thead>
                                <tr><th>Total Caja (Ventas - Gastos - Mermas)</th></tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <span class="total-monto">L. <?= number_format($totalCajaReal, 2) ?></span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </td>
                </tr>
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
