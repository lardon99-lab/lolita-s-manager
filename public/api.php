<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

$resources = [
    'clientes' => __DIR__ . '/../app/controllers/ClienteController.php',
    'catalogo' => __DIR__ . '/../app/controllers/CatalogoController.php',
    'inventario' => __DIR__ . '/../app/controllers/InventarioController.php',
    'pedidos' => __DIR__ . '/../app/controllers/PedidoController.php',
    'sucursales' => __DIR__ . '/../app/controllers/SucursalController.php',
    'usuarios' => __DIR__ . '/../app/controllers/UsuarioController.php',
    'ventas' => __DIR__ . '/../app/controllers/VentaController.php',
];

$resource = (string) ($_GET['resource'] ?? '');
if (!isset($resources[$resource])) {
    \App\Http\Response::json(['status' => 'error', 'message' => 'Recurso no encontrado.'], 404);
}
try {
    require $resources[$resource];
} catch (InvalidArgumentException $error) {
    \App\Http\Response::json([
        'status' => 'error',
        'message' => $error->getMessage(),
        'errors' => ['_form' => $error->getMessage()],
    ], 422);
} catch (PDOException $error) {
    \App\Support\Logger::error($error);
    $conflict = $error->getCode() === '23000';
    \App\Http\Response::json([
        'status' => 'error',
        'message' => $conflict ? 'Ya existe un registro con esos datos.' : 'No fue posible guardar los cambios.',
    ], $conflict ? 409 : 500);
} catch (Throwable $error) {
    \App\Support\Logger::error($error);
    \App\Http\Response::json(['status' => 'error', 'message' => 'Ocurrio un error interno.'], 500);
}
