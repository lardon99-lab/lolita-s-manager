<?php
// Archivo exclusivo para generar PDFs sin cargar el menú lateral
require_once '../app/controllers/PedidoController.php';
\App\Security\Auth::requireLogin(false);
\App\Security\Auth::requirePermission('reports.view');
$pedidosCtrl = new PedidoController();
$today = date('Y-m-d');
// El control de caja exportado es siempre diario. Los demas filtros solo acotan ese dia.
$filtros = [
    'desde'    => $today,
    'hasta'    => $today,
    'busqueda' => !empty($_GET['busqueda']) ? \App\Http\Validator::text($_GET['busqueda'], 'busqueda', 100) : null,
    'sucursal' => !empty($_GET['sucursal']) ? (int) $_GET['sucursal'] : null,
    'tipo'     => !empty($_GET['tipo']) ? \App\Http\Validator::enum($_GET['tipo'], ['Pedido', 'Venta'], 'tipo') : null
];
if ($filtros['sucursal']) \App\Security\Auth::requirePermission('reports.view', $filtros['sucursal']);
// Ejecutamos la descarga directa
$pedidosCtrl->descargarReportePDF($filtros);
exit;
