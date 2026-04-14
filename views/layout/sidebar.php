<div class="sidebar shadow-sm" style="width: 250px; min-height: 100vh; background: white; border-right: 1px solid #eee; padding: 20px;">
    <div class="mb-5 text-center">
        <h4 class="fw-bold" style="color: #ff85a2;">🍰 Lolita's</h4>
        <small class="text-muted"><?php echo $_SESSION['role']; ?></small>
    </div>
    <nav class="nav flex-column">
        <a class="nav-link p-3 mb-2 rounded <?php echo (!isset($_GET['view']) || $_GET['view'] == 'dashboard') ? 'bg-primary text-white shadow-sm' : 'text-dark'; ?>" href="index.php?view=dashboard">
            <i class="fa-solid fa-chart-pie me-2"></i> Dashboard
        </a>
        <a class="nav-link p-3 mb-2 rounded <?php echo (isset($_GET['view']) && $_GET['view'] == 'inventario') ? 'bg-primary text-white shadow-sm' : 'text-dark'; ?>" href="index.php?view=inventario">
            <i class="fa-solid fa-boxes-stacked me-2"></i> Inventario
        </a>
        <a class="nav-link p-3 mb-2 rounded <?php echo (isset($_GET['view']) && $_GET['view'] == 'pedidos-nuevo') ? 'bg-primary text-white shadow-sm' : 'text-dark'; ?>" href="index.php?view=pedidos-nuevo">
            <i class="fa-solid fa-calendar-check me-2"></i> Nuevo Pedido
        </a>
        <a class="nav-link p-3 mb-2 rounded <?php echo (isset($_GET['view']) && $_GET['view'] == 'pedidos-listar') ? 'bg-primary text-white shadow-sm' : 'text-dark'; ?>" href="index.php?view=pedidos-listar">
            <i class="fa-solid fa-calendar-alt me-2"></i> Listar Pedidos
        </a>
        <a class="nav-link p-3 mb-2 rounded <?= (isset($_GET['view']) && $_GET['view'] == 'ventas-historial') ? 'bg-primary text-white shadow-sm' : 'text-dark'; ?>" href="index.php?view=ventas-historial">
            <i class="fa-solid fa-file-invoice-dollar me-2"></i> Historial Ventas
        </a>
        <hr>
        <a class="nav-link p-3 text-danger" href="../app/controllers/AuthController.php?action=logout">
            <i class="fa-solid fa-right-from-bracket me-2"></i> Salir
        </a>
    </nav>
</div>

<style>
    .nav-link:hover { background-color: #f8f9fa; }
    .bg-primary { background-color: #ff85a2 !important; border: none; }
</style>