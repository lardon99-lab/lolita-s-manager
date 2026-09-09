<?php
// views/dashboard/index.php

?>

<div class="container-fluid p-3 p-md-4"> 
    <div class="page-shell rounded-4 p-3 p-md-4 mb-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
            <div>
                <h2 class="page-title fw-bold mb-1">Resumen de Lolita's</h2>
                <p class="page-subtitle mb-0">Un vistazo rápido al estado de tu negocio.</p>
            </div>
            <button class="btn btn-light d-md-none border-0 shadow-sm rounded-3 p-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu">
                <i class="fa-solid fa-bars fa-xl text-primary"></i>
            </button>
        </div>
    </div>

    <!-- Mensajes de estado alternativos -->
    <?php if (isset($_GET['status']) && $_GET['status'] == 'merma_registrada'): ?>
        <div class="alert alert-success alert-dismissible fade show rounded-4 shadow-sm mb-4" role="alert">
            <i class="fa-solid fa-circle-check me-2"></i> ¡Merma registrada correctamente! El stock se redujo a cero.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Tarjetas de Métricas -->
    <div class="row g-3 g-md-4 mb-4">
        <div class="col-12 col-sm-6 col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 text-white h-100 position-relative overflow-hidden" style="background: linear-gradient(135deg, #ff85a2 0%, #ff6b8b 100%);">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="text-uppercase small fw-bold opacity-75 mb-1">Pedidos Pendientes</h6>
                            <h2 class="display-5 fw-bold mb-0"><?= $metricas['pendientes'] ?></h2>
                        </div>
                        <div class="bg-white bg-opacity-25 rounded-3 p-3">
                            <i class="fa-solid fa-clock fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 text-white h-100 position-relative overflow-hidden" style="background: linear-gradient(135deg, #f6d365 0%, #fda085 100%);">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="text-uppercase small fw-bold opacity-75 mb-1">Entregas para Hoy</h6>
                            <h2 class="display-5 fw-bold mb-0"><?= $metricas['para_hoy'] ?></h2>
                        </div>
                        <div class="bg-white bg-opacity-25 rounded-3 p-3">
                            <i class="fa-solid fa-calendar-day fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 text-white h-100 position-relative overflow-hidden" style="background: linear-gradient(135deg, #20c997 0%, #12a57a 100%);">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="text-uppercase small fw-bold opacity-75 mb-1">Ventas Completadas</h6>
                            <h2 class="fs-1 fw-bold mb-0">L. <?= number_format($metricas['ventas'], 2) ?></h2>
                        </div>
                        <div class="bg-white bg-opacity-25 rounded-3 p-3">
                            <i class="fa-solid fa-money-bill-trend-up fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ALERTA 1: PRODUCTOS YA CADUCADOS (CRÍTICO) -->
    <?php if (count($productos_caducados) > 0): ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="alert alert-danger shadow-sm border-danger border-start border-5 mb-0 rounded-4 p-4 d-flex flex-column flex-md-row align-items-md-center gap-3" role="alert">
                <div class="bg-danger bg-opacity-15 rounded-circle p-3 d-inline-flex align-items-center justify-content-center text-danger" style="width: 60px; height: 60px; min-width: 60px;">
                    <i class="fa-solid fa-circle-exclamation fa-2x"></i>
                </div>
                <div class="flex-grow-1">
                    <h5 class="alert-heading fw-bold text-danger mb-1">Productos Caducados</h5>
                    <p class="small text-secondary mb-2">Los siguientes lotes han vencido. Deben ser retirados y registrados como merma para inactivar su venta.</p>
                    <ul class="mb-0 small ps-0 text-dark list-unstyled mt-2">
                        <?php foreach ($productos_caducados as $cad): 
                            $fecha_formateada = date('d/m/Y', strtotime($cad['fecha_caducidad']));
                        ?>
                            <li class="mb-2 d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center border-bottom border-danger border-opacity-10 pb-2">
                                <div class="mb-2 mb-sm-0">
                                    <strong class="text-dark"><?= htmlspecialchars($cad['nombre_producto']) ?></strong> 
                                    <span class="text-secondary small ms-1">
                                        (<i class="fa-solid fa-store fa-xs"></i> <?= htmlspecialchars($cad['nombre_sucursal']) ?>)
                                    </span>
                                    <span class="badge bg-danger text-white mx-1"><?= $cad['stock_actual'] ?> unid.</span> 
                                    <span class="text-muted">- Venció el: <span class="text-danger fw-bold"><?= $fecha_formateada ?></span></span>
                                </div>
                                
                                <button type="button" 
                                        class="btn btn-sm btn-danger rounded-pill fw-bold px-3 btn-mermar-caducado" 
                                        data-id="<?= $cad['id_inventario'] ?>" 
                                        data-nombre="<?= htmlspecialchars($cad['nombre_producto']) ?>">
                                    <i class="fa-solid fa-trash-can me-1"></i> Registrar Merma
                                </button>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ALERTA 2: PRÓXIMOS A CADUCAR -->
    <?php if (count($productos_por_caducar) > 0): ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="alert alert-warning shadow-sm border-warning border-start border-5 mb-0 rounded-4 p-4 d-flex flex-column flex-md-row align-items-md-center gap-3" role="alert">
                <div class="bg-warning bg-opacity-25 rounded-circle p-3 d-inline-flex align-items-center justify-content-center text-warning" style="width: 60px; height: 60px; min-width: 60px;">
                    <i class="fa-solid fa-bell fa-2x"></i>
                </div>
                <div class="flex-grow-1">
                    <h5 class="alert-heading fw-bold mb-1">Próximos a caducar (Siguientes 7 días)</h5>
                    <ul class="mb-0 small ps-3 text-dark mt-2">
                        <?php foreach ($productos_por_caducar as $alerta_cad): 
                            $fecha_formateada = date('d/m/Y', strtotime($alerta_cad['fecha_caducidad']));
                        ?>
                            <li class="mb-1">
                                <strong><?= htmlspecialchars($alerta_cad['nombre_producto']) ?></strong> 
                                <span class="text-secondary small ms-1">
                                    (<i class="fa-solid fa-store fa-xs"></i> <?= htmlspecialchars($alerta_cad['nombre_sucursal']) ?>)
                                </span>
                                <span class="badge bg-warning text-dark mx-1"><?= $alerta_cad['stock_actual'] ?> unid.</span> 
                                <span class="text-muted">- Vence: <span class="text-danger fw-bold"><?= $fecha_formateada ?></span></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Inventario crítico -->
    <div class="row mb-4">
        <div class="col-12">
            <section class="critical-inventory" aria-labelledby="criticalInventoryTitle">
                <header class="critical-inventory__header">
                    <div>
                        <h5 id="criticalInventoryTitle" class="fw-bold mb-1">
                            <i class="fa-solid fa-triangle-exclamation text-danger me-2"></i>Inventario crítico
                        </h5>
                        <p class="mb-0">Revisa y abastece los productos que requieren atención.</p>
                    </div>
                    <span class="critical-inventory__total">
                        <?= count($inventario_agotado) + count($inventario_stock_bajo) ?> productos
                    </span>
                </header>

                <?php
                $inventoryMenus = [
                    ['title' => 'Inventario agotado', 'description' => 'Productos sin unidades disponibles', 'icon' => 'fa-circle-xmark', 'variant' => 'danger', 'items' => $inventario_agotado],
                    ['title' => 'Stock bajo', 'description' => 'Productos por debajo del mínimo definido', 'icon' => 'fa-arrow-trend-down', 'variant' => 'warning', 'items' => $inventario_stock_bajo],
                ];
                ?>

                <div class="critical-accordion accordion" id="criticalInventoryAccordion">
                    <?php foreach ($inventoryMenus as $menuIndex => $menu): ?>
                        <?php $collapseId = 'criticalInventoryPanel' . $menuIndex; ?>
                        <div class="accordion-item critical-panel critical-panel--<?= $menu['variant'] ?>">
                            <h6 class="accordion-header">
                                <button class="accordion-button critical-panel__toggle <?= $menuIndex === 0 ? '' : 'collapsed' ?>"
                                        type="button"
                                        data-bs-toggle="collapse"
                                        data-bs-target="#<?= $collapseId ?>"
                                        aria-expanded="<?= $menuIndex === 0 ? 'true' : 'false' ?>"
                                        aria-controls="<?= $collapseId ?>">
                                    <span class="critical-panel__icon" aria-hidden="true"><i class="fa-solid <?= $menu['icon'] ?>"></i></span>
                                    <span class="critical-panel__heading">
                                        <strong><?= e($menu['title']) ?></strong>
                                        <small><?= e($menu['description']) ?></small>
                                    </span>
                                    <span class="critical-panel__count"><?= count($menu['items']) ?></span>
                                </button>
                            </h6>

                            <div id="<?= $collapseId ?>" class="accordion-collapse collapse <?= $menuIndex === 0 ? 'show' : '' ?>">
                                <div class="accordion-body critical-panel__body">
                                    <?php if ($menu['items'] === []): ?>
                                        <p class="critical-panel__empty mb-0"><i class="fa-solid fa-circle-check me-2"></i>No hay productos en esta categoría.</p>
                                    <?php else: ?>
                                        <div class="d-none d-md-block table-responsive">
                                            <table class="table critical-table mb-0">
                                                <thead>
                                                    <tr><th scope="col">Producto</th><th scope="col">Sucursal</th><th scope="col">Stock</th><th scope="col" class="text-end">Acción</th></tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($menu['items'] as $item): ?>
                                                        <tr>
                                                            <th scope="row"><?= e($item['nombre_producto']) ?></th>
                                                            <td><i class="fa-solid fa-store me-2 text-muted" aria-hidden="true"></i><?= e($item['nombre_sucursal']) ?></td>
                                                            <td>
                                                                <span class="stock-chip stock-chip--<?= (int) $item['stock_actual'] <= 0 ? 'danger' : 'warning' ?>">
                                                                    <?= (int) $item['stock_actual'] <= 0 ? 'Agotado' : (int) $item['stock_actual'] . ' unid.' ?>
                                                                </span>
                                                            </td>
                                                            <td class="text-end"><?php include __DIR__ . '/partials/critical-stock-button.php'; ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>

                                        <table class="table critical-mobile-table d-md-none mb-0">
                                            <thead><tr><th scope="col">Producto</th><th scope="col" class="text-center">Acción</th></tr></thead>
                                            <tbody>
                                                <?php foreach ($menu['items'] as $item): ?>
                                                    <tr>
                                                        <th scope="row">
                                                            <span class="critical-mobile-table__name"><?= e($item['nombre_producto']) ?></span>
                                                            <span class="critical-mobile-table__meta"><i class="fa-solid fa-store" aria-hidden="true"></i><?= e($item['nombre_sucursal']) ?></span>
                                                            <span class="stock-chip stock-chip--<?= (int) $item['stock_actual'] <= 0 ? 'danger' : 'warning' ?>">
                                                                <?= (int) $item['stock_actual'] <= 0 ? 'Agotado' : (int) $item['stock_actual'] . ' unid.' ?>
                                                            </span>
                                                        </th>
                                                        <td class="text-center"><?php include __DIR__ . '/partials/critical-stock-button.php'; ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        </div>
    </div>

    <!-- Acciones Rápidas -->
    <div>
        <h6 class="fw-bold mb-3 text-uppercase text-muted small">Acciones Rápidas</h6>
        <div class="d-flex flex-wrap gap-3">
            <a href="index.php?view=pedidos-nuevo" class="btn btn-white shadow-sm border rounded-pill px-4 py-2 flex-grow-1 flex-md-grow-0 text-start fw-semibold hover-lift">
                <i class="fa-solid fa-plus text-primary border border-primary border-2 rounded-circle p-1 me-2" style="font-size: 0.7rem;"></i> Nuevo Pedido
            </a>
            <a href="index.php?view=inventario" class="btn btn-white shadow-sm border rounded-pill px-4 py-2 flex-grow-1 flex-md-grow-0 text-start fw-semibold hover-lift">
                <i class="fa-solid fa-boxes-stacked text-primary me-2"></i> Ver Inventario
            </a>
        </div>
    </div>
</div>

<link rel="stylesheet" href="css/views/dashboard.css?v=<?= filemtime(__DIR__ . '/../../public/css/views/dashboard.css') ?>">

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.26.25"></script>

<script src="js/views/dashboard.js"></script>
