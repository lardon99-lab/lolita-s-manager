<div id="sidebarMenu" class="sidebar offcanvas-md offcanvas-start shadow-sm bg-white" tabindex="-1" aria-labelledby="sidebarTitle">
    
    <div class="offcanvas-header d-md-none border-bottom px-4">
        <h5 class="offcanvas-title fw-bold" id="sidebarTitle">Lolita's</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#sidebarMenu" aria-label="Cerrar"></button>
    </div>

    <div class="offcanvas-body d-flex flex-column p-3 p-md-4">
        <div class="mb-4 text-center">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <h4 class="fw-bold d-none d-md-block mb-0 sidebar-brand-title">Lolita's</h4>
                <button type="button" class="btn btn-sm btn-light border shadow-sm d-none d-md-inline-flex sidebar-toggle" id="sidebarToggle" aria-label="Contraer menú" title="Contraer o expandir menú">
                    <i class="fa-solid fa-chevron-left"></i>
                </button>
            </div>
            <div class="bg-light rounded-pill py-2 px-3 mt-2 d-inline-flex align-items-center shadow-sm sidebar-user-badge">
                <i class="fa-solid fa-circle-user text-muted me-2"></i>
                <small class="sidebar-user-meta text-secondary fw-semibold"><?= e($_SESSION['username'] ?? '') ?></small>
            </div>
        </div>
        
        <nav class="nav flex-column w-100 gap-2">
            <a class="nav-link px-3 py-2 rounded-4 d-flex align-items-center transition-all <?php echo (!isset($_GET['view']) || $_GET['view'] == 'dashboard') ? 'bg-primary text-white shadow-sm fw-bold' : 'text-secondary menu-hover'; ?>" href="index.php?view=dashboard">
                <i class="fa-solid fa-chart-pie me-3 fs-5" style="width: 24px;"></i><span class="sidebar-label">Dashboard</span>
            </a>
            <a class="nav-link px-3 py-2 rounded-4 d-flex align-items-center transition-all <?php echo (isset($_GET['view']) && $_GET['view'] == 'inventario') ? 'bg-primary text-white shadow-sm fw-bold' : 'text-secondary menu-hover'; ?>" href="index.php?view=inventario">
                <i class="fa-solid fa-boxes-stacked me-3 fs-5" style="width: 24px;"></i><span class="sidebar-label">Inventario</span>
            </a>
            <a class="nav-link px-3 py-2 rounded-4 d-flex align-items-center transition-all <?php echo (isset($_GET['view']) && $_GET['view'] == 'pedidos-nuevo') ? 'bg-primary text-white shadow-sm fw-bold' : 'text-secondary menu-hover'; ?>" href="index.php?view=pedidos-nuevo">
                <i class="fa-solid fa-calendar-check me-3 fs-5" style="width: 24px;"></i><span class="sidebar-label">Nuevo Pedido</span>
            </a>
            <a class="nav-link px-3 py-2 rounded-4 d-flex align-items-center transition-all <?php echo (isset($_GET['view']) && $_GET['view'] == 'pedidos-lista') ? 'bg-primary text-white shadow-sm fw-bold' : 'text-secondary menu-hover'; ?>" href="index.php?view=pedidos-lista">
                <i class="fa-solid fa-calendar-alt me-3 fs-5" style="width: 24px;"></i><span class="sidebar-label">Listar Pedidos</span>
            </a>
            <a class="nav-link px-3 py-2 rounded-4 d-flex align-items-center transition-all <?= (isset($_GET['view']) && $_GET['view'] == 'ventas-historial') ? 'bg-primary text-white shadow-sm fw-bold' : 'text-secondary menu-hover'; ?>" href="index.php?view=ventas-historial">
                <i class="fa-solid fa-file-invoice-dollar me-3 fs-5" style="width: 24px;"></i><span class="sidebar-label">Historial Ventas</span>
            </a>
            
            <?php if (in_array((int) ($_SESSION['id_rol'] ?? 0), [1, 3], true)): ?>
            <a class="nav-link px-3 py-2 rounded-4 d-flex align-items-center transition-all <?php echo (isset($_GET['view']) && $_GET['view'] == 'usuarios') ? 'bg-primary text-white shadow-sm fw-bold' : 'text-secondary menu-hover'; ?>" href="index.php?view=usuarios">
                <i class="fa-solid fa-user-shield me-3 fs-5" style="width: 24px;"></i><span class="sidebar-label">Usuarios</span>
            </a>
            <?php endif; ?>

            <a class="nav-link px-3 py-2 rounded-4 d-flex align-items-center transition-all <?= (isset($_GET['view']) && $_GET['view'] == 'ventas-nueva') ? 'bg-primary text-white shadow-sm fw-bold' : 'text-secondary menu-hover'; ?>" href="index.php?view=ventas-nueva">
                <i class="fa-solid fa-cash-register me-3 fs-5" style="width: 24px;"></i><span class="sidebar-label">Registrar Venta</span>
            </a>
            
            <hr class="sidebar-divider text-black-50 my-3">
            
            <form action="auth.php?action=logout" method="POST">
                <button class="nav-link border-0 bg-transparent w-100 px-3 py-2 text-danger rounded-4 d-flex align-items-center menu-hover-danger" type="submit">
                    <i class="fa-solid fa-right-from-bracket me-3 fs-5" style="width: 24px;"></i><span class="sidebar-label">Salir</span>
                </button>
            </form>
        </nav>
    </div>
</div>

<link rel="stylesheet" href="css/views/sidebar.css?v=<?= filemtime(__DIR__ . '/../../public/css/views/sidebar.css') ?>">

<script src="js/views/sidebar.js"></script>
