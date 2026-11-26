<?php
// Archivo exclusivo para generar PDFs sin cargar el menú lateral
require_once '../app/controllers/PedidoController.php';
\App\Security\Auth::requireLogin(false);
\App\Security\Auth::requirePermission('reports.view');
$pedidosCtrl = new PedidoController();
$pedidosCtrl->descargarReportePDF(['sucursal' => $_GET['sucursal'] ?? null]);
exit;
