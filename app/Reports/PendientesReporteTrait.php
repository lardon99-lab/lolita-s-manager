<?php

use App\Security\Auth;

trait PendientesReporteTrait
{
    public function generarReportePendientes() {
        Auth::requirePermission('reports.view');

        $hoy = date('Y-m-d');

        try {
            $query = "SELECT p.id_pedido, p.fecha_entrega, p.total_pedido, p.saldo_pendiente,
                            c.nombre_completo as cliente, s.nombre_sucursal as sucursal,
                            det.cantidad, det.precio_unitario, det.detalles_personalizacion, det.subtotal,
                            dd.color_descripcion, dd.frase, dd.instrucciones,
                            dd.archivo_nombre_interno,
                            (SELECT GROUP_CONCAT(CONCAT(pdo.grupo_nombre, ': ', pdo.opcion_nombre)
                                ORDER BY pdo.id_detalle_opcion SEPARATOR ' | ')
                             FROM pedido_detalle_opciones pdo
                             WHERE pdo.id_detalle = det.id_detalle) AS opciones_personalizacion
                    FROM pedidos p 
                    JOIN clientes c ON p.id_cliente = c.id_cliente
                    JOIN sucursales s ON p.id_sucursal = s.id_sucursal
                    JOIN pedido_detalles det ON p.id_pedido = det.id_pedido
                    LEFT JOIN pedido_detalle_diseno dd ON dd.id_detalle = det.id_detalle
                    WHERE p.estado = 'Pendiente' 
                    AND DATE(p.fecha_entrega) = :hoy";

            $params = [':hoy' => $hoy];
            $allowed = Auth::allowedBranches('reports.view');
            if ($allowed !== null) {
                if ($allowed === []) {
                    http_response_code(403);
                    echo 'No hay sucursales asignadas para generar este reporte.';
                    return;
                }
                $holders = [];
                foreach ($allowed as $index => $branchId) {
                    $key = ':report_branch_' . $index;
                    $holders[] = $key;
                    $params[$key] = $branchId;
                }
                $query .= ' AND p.id_sucursal IN (' . implode(',', $holders) . ')';
            }
            $query .= ' ORDER BY p.fecha_entrega ASC, p.id_pedido ASC';
            
            $stmt = $this->db->prepare($query);
            
            $stmt->execute($params);
            $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $pedidosAgrupados = [];
            foreach ($pedidos as $pedido) {
                $idPedido = (int)$pedido['id_pedido'];
                if (!isset($pedidosAgrupados[$idPedido])) {
                    $pedidosAgrupados[$idPedido] = [
                        'total_pedido' => (float)$pedido['total_pedido'],
                        'saldo_pendiente' => (float)$pedido['saldo_pendiente']
                    ];
                }
            }
            
            $totalGeneral = array_sum(array_column($pedidosAgrupados, 'total_pedido'));
            $totalSaldos = array_sum(array_column($pedidosAgrupados, 'saldo_pendiente'));
            $totalPedidos = count($pedidosAgrupados);

            ob_start();
            ?>
            <html>
            <head>
                <style>
                    body { font-family: 'Helvetica', sans-serif; font-size: 10px; color: #333; }
                    .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #ff69b4; padding-bottom: 10px; }
                    .titulo { font-size: 18px; font-weight: bold; color: #d63384; }
                    .subtitulo { font-size: 12px; color: #666; margin-top: 5px; }
                    .table { width: 100%; border-collapse: collapse; margin-top: 10px; }
                    .table th { background-color: #f8f9fa; border: 1px solid #dee2e6; padding: 8px; text-align: left; font-size: 9px; text-transform: uppercase; }
                    .table td { border: 1px solid #dee2e6; padding: 7px; vertical-align: middle; }
                    .text-end { text-align: right; }
                    .footer-totals { margin-top: 15px; padding: 10px; background-color: #fff0f5; border-radius: 5px; }
                    .saldo-rojo { color: #dc3545; font-weight: bold; }
                </style>
            </head>
            <body>
                <div class="header">
                    <div class="titulo">REPORTE DE ENTREGAS DIARIAS</div>
                    <div class="subtitulo">Pedidos Pendientes</div>
                    <p><strong>Lolita's Manager</strong><br>
                    Generado el: <?= date('d/m/Y') ?> - <?= date('l') ?><br>
                    a las: <?= date('h:i:s A') ?></p>
                </div>

                <?php if (empty($pedidos)): ?>
                    <div style="text-align: center; margin-top: 50px; color: #666;">
                        <h3>No hay pedidos pendientes programados para hoy.</h3>
                        <p>Fecha consultada: <?= date('d/m/Y') ?></p>
                    </div>
                <?php else: ?> 
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Cantidad</th>
                                <th class="text-end">Precio</th>
                                <th>Personalizacion</th>
                                <th>Fecha Entrega</th>
                                <th>Cliente</th>
                                <th>Sucursal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($pedidos as $p): ?>
                            <tr>
                                <td><strong><?= (int)$p['cantidad'] ?></strong></td>
                                <td class="text-end">L. <?= number_format((float)$p['precio_unitario'], 2) ?></td>
                                <td style="font-size: 8.5px;"><?php
                                    $design = array_filter([
                                        !empty($p['color_descripcion']) ? 'Color: ' . $p['color_descripcion'] : '',
                                        !empty($p['frase']) ? 'Frase: ' . $p['frase'] : '',
                                        $p['instrucciones'] ?? '',
                                        !empty($p['archivo_nombre_interno']) ? 'Imagen de referencia adjunta' : '',
                                    ]);
                                    $specifications = array_filter([
                                        $p['opciones_personalizacion'] ?? '',
                                        $design !== [] ? implode(' | ', $design) : '',
                                        $p['detalles_personalizacion'] ?? '',
                                    ]);
                                    echo htmlspecialchars($specifications !== [] ? implode(' | ', $specifications) : 'Sin especificaciones');
                                ?></td>
                                <td><strong><?= date('d/m/Y', strtotime($p['fecha_entrega'])) ?></strong></td>
                                <td><?= htmlspecialchars($p['cliente']) ?></td>
                                <td><?= htmlspecialchars($p['sucursal']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <div class="footer-totals">
                        <table style="width: 100%;">
                            <tr>
                                <td style="width: 50%;"><strong>TOTAL PEDIDOS:</strong> <?= $totalPedidos ?></td>
                                <td class="text-end">
                                    <strong>VENTA TOTAL: L. <?= number_format($totalGeneral, 2) ?></strong><br>
                                    <strong class="saldo-rojo">POR COBRAR: L. <?= number_format($totalSaldos, 2) ?></strong>
                                </td>
                            </tr>
                        </table>
                    </div>
                <?php endif; ?>
            </body>
            </html>
            <?php
            $html = ob_get_clean();

            if (ob_get_length()) ob_end_clean();

            $dompdf = new \Dompdf\Dompdf();
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            $dompdf->stream("Pendientes_HN_".date('d_m_Y').".pdf", ["Attachment" => false]);
            exit;

        } catch (Exception $e) {
            \App\Support\Logger::error($e);
            die("No fue posible generar el reporte.");
        }
    }
}
