<?php
require_once __DIR__ . '/PedidoController.php';
\App\Security\Auth::requireLogin(false);
// Nota: Debes instalar dompdf vía composer: composer require dompdf/dompdf
require_once __DIR__ . '/../../vendor/autoload.php';

use Dompdf\Dompdf;

if (isset($_GET['fecha'])) {
    $fecha = $_GET['fecha'];
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $fecha);
    if (!$date || $date->format('Y-m-d') !== $fecha) {
        http_response_code(422);
        exit('Fecha invalida.');
    }
    $ctrl = new PedidoController();
    $ventas = $ctrl->obtenerHistorialVentas(['desde' => $fecha, 'hasta' => $fecha]);
    $totalDia = array_sum(array_column($ventas, 'total_pedido'));

    $html = "<h1>Reporte de Ventas - " . htmlspecialchars($fecha, ENT_QUOTES, 'UTF-8') . "</h1>";
    $html .= "<table border='1' width='100%' style='border-collapse: collapse;'>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tipo</th>
                        <th>Detalle</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>";
    
    foreach ($ventas as $v) {
        $html .= "<tr>
                    <td>#{$v['id_pedido']}</td>
                    <td>" . htmlspecialchars($v['tipo'], ENT_QUOTES, 'UTF-8') . "</td>
                    <td>" . htmlspecialchars($v['cliente']) . "</td>
                    <td>L. " . number_format($v['total_pedido'], 2) . "</td>
                  </tr>";
    }
    
    $html .= "</tbody></table>";
    $html .= "<h3>Total Diario: L. " . number_format($totalDia, 2) . "</h3>";

    $dompdf = new Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->render();
    $dompdf->stream("Reporte_$fecha.pdf", ["Attachment" => true]);
}
