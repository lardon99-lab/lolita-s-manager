<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

$resources = [
    'clientes' => __DIR__ . '/../app/controllers/ClienteController.php',
    'inventario' => __DIR__ . '/../app/controllers/InventarioController.php',
    'pedidos' => __DIR__ . '/../app/controllers/PedidoController.php',
    'usuarios' => __DIR__ . '/../app/controllers/UsuarioController.php',
    'ventas' => __DIR__ . '/../app/controllers/VentaController.php',
];

$resource = (string) ($_GET['resource'] ?? '');
if (!isset($resources[$resource])) {
    \App\Http\Response::json(['status' => 'error', 'message' => 'Recurso no encontrado.'], 404);
}
require $resources[$resource];
