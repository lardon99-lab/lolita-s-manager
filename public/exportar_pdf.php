<?php
// Archivo exclusivo para generar PDFs sin cargar el menú lateral
require_once '../app/controllers/PedidoController.php';
\App\Security\Auth::requireLogin(false);
\App\Security\Auth::requirePermission('reports.view');
$pedidosCtrl = new PedidoController();
$today = date('Y-m-d');
// El control de caja exportado es siempre diario. Los demas filtros solo acotan ese dia.
$filtros = \App\Http\Input\ReportFilters::from(array_merge($_GET, [
    'desde' => $today,
    'hasta' => $today,
]), $today);
if ($filtros['sucursal']) \App\Security\Auth::requirePermission('reports.view', $filtros['sucursal']);
// Ejecutamos la descarga directa
$pedidosCtrl->descargarReportePDF($filtros);
exit;
