<?php
// public/index.php
session_start();

// Si no hay sesión activa, redirigir al login
if (!isset($_SESSION['id_usuario'])) {
    header("Location: ../views/auth/login.php");
    exit();
}

// Lógica de enrutamiento simple
$view = isset($_GET['view']) ? $_GET['view'] : 'dashboard';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Lolita's DB - Sistema de Gestión</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/estilos.css"> </head>
<body>

    <div class="d-flex">
        <?php include '../views/layout/sidebar.php'; ?>

        <div class="main-content w-100">
            <?php 
                switch($view) {
                    case 'inventario':
                        include '../views/inventario/listar.php';
                        break;
                    case 'pedidos-nuevo':
                        require_once '../app/controllers/PedidoController.php';
                        $pedidosCtrl = new PedidoController();
                        $data = $pedidosCtrl->prepararFormulario();
                        $clientes = $data['clientes'];
                        $productos = $data['productos'];
                        $sucursales = $data['sucursales'];
                        include '../views/pedidos/nuevo.php';
                        break;
                    case 'dashboard':
                        include '../views/dashboard/index.php';
                        break;
                    case 'pedidos-lista':
                        require_once '../app/controllers/PedidoController.php';
                        $pedidosCtrl = new PedidoController();
                        
                        // Capturamos el estado, si no viene en la URL, por defecto es 'Todos'
                        $filtro_estado = isset($_GET['estado']) ? $_GET['estado'] : 'Todos';
                        
                        // Ejecutamos la consulta
                        $listado = $pedidosCtrl->listarTodos($filtro_estado);
                        
                        // Cargamos la vista
                        include '../views/pedidos/listar.php';
                        break;
                    case 'ventas-historial':
                        include '../views/pedidos/historial.php';
                        break;
                    default:
                        include '../views/dashboard/index.php';
                        break;
                    case 'usuarios':
                    if ($_SESSION['role'] !== 'Admin') {
                        header("Location: index.php?view=dashboard");
                        exit();
                    }
                    require_once '../app/controllers/UsuarioController.php'; // Ahora sí lo encontrará
                    $userCtrl = new UsuarioController();
                    $usuarios = $userCtrl->listar();
                    $sucursales = $userCtrl->obtenerSucursales();
                    include '../views/usuarios/listar.php';
                    break;

                    case 'ventas-nueva':
                    require_once '../app/controllers/InventarioController.php';
                    require_once '../app/controllers/VentaController.php';
                    
                    $invCtrl = new InventarioController();
                    // Usamos el método de inventario para traer productos con existencias
                    $productos = $invCtrl->listarProductosDisponibles(); 
                    
                    include '../views/ventas/nueva.php';
                    break;
                }
            ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>