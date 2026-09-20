<?php
// public/index.php
require_once __DIR__ . '/../app/core/SesionHelper.php';
require_once __DIR__ . '/../app/core/Database.php';

use App\Security\Auth;
use App\Security\Csrf;

// El helper ya inicia sesión y valida si existe el id_usuario
SesionHelper::protegerVista();

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

$view = isset($_GET['view']) ? $_GET['view'] : 'dashboard';
$allowedViews = ['dashboard', 'inventario', 'pedidos-nuevo', 'pedidos-lista', 'ventas-historial', 'ventas-nueva', 'usuarios', 'clientes', 'sucursales', 'catalogo'];
if (!in_array($view, $allowedViews, true)) {
    http_response_code(404);
    echo 'Vista no encontrada.';
    exit;
}
$viewPermissions = [
    'inventario' => 'inventory.view',
    'pedidos-nuevo' => 'orders.create',
    'pedidos-lista' => 'orders.view',
    'ventas-historial' => 'reports.view',
    'ventas-nueva' => 'sales.create',
    'usuarios' => 'users.manage',
    'clientes' => 'clients.manage',
    'sucursales' => 'branches.manage',
    'catalogo' => 'products.manage',
];
if (isset($viewPermissions[$view]) && !Auth::hasPermission($viewPermissions[$view])) {
    http_response_code(403);
    echo 'No tienes permiso para acceder a esta vista.';
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#ff85a2">
    <meta name="csrf-token" content="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">
    <title>Lolita's DB - Sistema de Gestión</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/estilos.css?v=<?= filemtime(__DIR__ . '/css/estilos.css') ?>">
    <link rel="stylesheet" href="css/components.css?v=<?= filemtime(__DIR__ . '/css/components.css') ?>">
</head>
<body data-is-admin="<?= Auth::hasPermission('products.manage') ? '1' : '0' ?>">

    <div class="app-layout d-flex flex-column flex-md-row">
        <?php include __DIR__ . '/../views/layout/sidebar.php'; ?>

        <div class="main-content w-100">
            <div class="mobile-topbar d-md-none">
                <span class="brand-badge">
                    <i class="fa-solid fa-cake-candles"></i>
                    Lolita's
                </span>
                <button class="btn btn-sm btn-light border shadow-sm rounded-pill px-3" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu">
                    <i class="fa-solid fa-bars"></i>
                </button>
            </div>

            <?php 
                switch($view) {
                    case 'dashboard':
                        $dashboardData = (new \App\Services\DashboardService((new Database())->getConnection()))->data();
                        extract($dashboardData, EXTR_SKIP);
                        $dashboardPrepared = true;
                        include __DIR__ . '/../views/dashboard/index.php';
                        break;

                    case 'inventario':
                        $requestedBranch = isset($_GET['sucursal_id']) && $_GET['sucursal_id'] !== '' ? (int) $_GET['sucursal_id'] : null;
                        extract((new \App\Services\InventarioPageService((new Database())->getConnection()))->data($requestedBranch), EXTR_SKIP);
                        $inventoryPrepared = true;
                        include __DIR__ . '/../views/inventario/listar.php';
                        break;

                    case 'pedidos-nuevo':
                        require_once __DIR__ . '/../app/controllers/PedidoController.php';
                        $pedidosCtrl = new PedidoController();
                        $data = $pedidosCtrl->prepararFormulario();
                        $productos = $data['productos'];
                        $configuraciones = $data['configuraciones'];
                        $configuraciones_diseno = $data['configuraciones_diseno'];
                        $sucursales = $data['sucursales'];
                        include __DIR__ . '/../views/pedidos/nuevo.php';
                        break;

                    case 'pedidos-lista':
                        require_once __DIR__ . '/../app/controllers/PedidoController.php';
                        $pedidosCtrl = new PedidoController();
                        $allowedStates = ['Todos', 'Pendiente', 'En Preparación', 'Listo', 'Entregado', 'Cancelado'];
                        $filtro_estado = in_array($_GET['estado'] ?? 'Pendiente', $allowedStates, true) ? ($_GET['estado'] ?? 'Pendiente') : 'Pendiente';
                        $listado = $pedidosCtrl->listarTodos($filtro_estado);
                        include __DIR__ . '/../views/pedidos/listar.php';
                        break;

                    case 'ventas-historial':
                        include __DIR__ . '/../views/pedidos/historial.php';
                        break;

                    case 'ventas-nueva':
                        require_once __DIR__ . '/../app/controllers/InventarioController.php';
                        $invCtrl = new InventarioController();
                        $productos = $invCtrl->listarProductosDisponibles(); 
                        include __DIR__ . '/../views/ventas/nueva.php';
                        break;

                    case 'usuarios':
                        require_once __DIR__ . '/../app/controllers/UsuarioController.php';
                        $userCtrl = new UsuarioController();
                        $usuarios = $userCtrl->listar();
                        $sucursales = $userCtrl->obtenerSucursales();
                        $roles = $userCtrl->obtenerRolesAsignables();
                        include __DIR__ . '/../views/usuarios/listar.php';
                        break;

                    case 'clientes':
                        require_once __DIR__ . '/../app/controllers/ClienteController.php';
                        $clientes = (new ClienteController())->listarTodos();
                        include __DIR__ . '/../views/clientes/listar.php';
                        break;

                    case 'sucursales':
                        require_once __DIR__ . '/../app/controllers/SucursalController.php';
                        $sucursales = (new SucursalController())->listar();
                        include __DIR__ . '/../views/sucursales/listar.php';
                        break;

                    case 'catalogo':
                        require_once __DIR__ . '/../app/controllers/CatalogoController.php';
                        extract((new CatalogoController())->listar(), EXTR_SKIP);
                        include __DIR__ . '/../views/catalogo/listar.php';
                        break;

                    default:
                        http_response_code(404);
                        echo '<div class="container-fluid p-4"><div class="alert alert-warning">Vista no encontrada.</div></div>';
                        break;
                }
            ?>
        </div>
    </div>

    <script src="js/app.js"></script>
    <script src="js/components/custom-select.js?v=<?= filemtime(__DIR__ . '/js/components/custom-select.js') ?>"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
</body>
</html>
