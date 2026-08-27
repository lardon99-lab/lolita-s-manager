<?php
// Archivo exclusivo para generar PDFs sin cargar el menú lateral
require_once '../app/controllers/PedidoController.php';
\App\Security\Auth::requireLogin(false);
$pedidosCtrl = new PedidoController();
// Recibimos los mismos filtros
$filtros = [
    'desde'    => !empty($_GET['desde']) ? filter_var($_GET['desde'], FILTER_SANITIZE_SPECIAL_CHARS) : null,
    'hasta'    => !empty($_GET['hasta']) ? filter_var($_GET['hasta'], FILTER_SANITIZE_SPECIAL_CHARS) : null,
    'busqueda' => !empty($_GET['busqueda']) ? $_GET['busqueda'] : null,
    'sucursal' => !empty($_GET['sucursal']) ? (int) $_GET['sucursal'] : null,
    'tipo'     => !empty($_GET['tipo']) ? $_GET['tipo'] : null
];
if ($filtros['sucursal']) \App\Security\Auth::requireBranch($filtros['sucursal']);
// Ejecutamos la descarga directa
$pedidosCtrl->descargarReportePDF($filtros);
exit;
