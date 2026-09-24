<?php
$inventoryCards = [
    ['title' => 'Próximos a caducar', 'description' => 'Vencen durante los siguientes 7 días', 'icon' => 'fa-calendar-days', 'variant' => 'expiry', 'items' => $productos_por_caducar, 'modal' => 'expiringInventoryModal'],
    ['title' => 'Inventario agotado', 'description' => 'Productos sin unidades disponibles', 'icon' => 'fa-circle-xmark', 'variant' => 'danger', 'items' => $inventario_agotado, 'modal' => 'emptyInventoryModal'],
    ['title' => 'Stock bajo', 'description' => 'Existencias iguales o menores al mínimo', 'icon' => 'fa-arrow-trend-down', 'variant' => 'warning', 'items' => $inventario_stock_bajo, 'modal' => 'lowInventoryModal'],
];
?>

<div class="container-fluid app-page dashboard-page p-2 p-md-4">
    <header class="app-page-header">
        <div class="app-page-header__main">
            <span class="app-page-header__icon" aria-hidden="true"><i class="fa-solid fa-chart-line"></i></span>
            <div class="app-page-header__copy">
                <h2 class="app-page-header__title">Resumen de Lolita's</h2>
                <p class="app-page-header__subtitle">Un vistazo rápido al estado de tu negocio.</p>
            </div>
        </div>
    </header>

    <?php if (($_GET['status'] ?? '') === 'merma_registrada'): ?>
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
        <i class="fa-solid fa-circle-check me-2"></i>Merma registrada correctamente.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
    </div>
    <?php endif; ?>

    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-lg-4"><div class="app-stat-card"><div class="app-stat-card__body"><div><p class="app-stat-card__label">Pedidos pendientes</p><p class="app-stat-card__value"><?= (int) $metricas['pendientes'] ?></p></div><span class="app-stat-card__icon"><i class="fa-solid fa-clock"></i></span></div></div></div>
        <div class="col-12 col-sm-6 col-lg-4"><div class="app-stat-card app-stat-card--warning"><div class="app-stat-card__body"><div><p class="app-stat-card__label">Entregas para hoy</p><p class="app-stat-card__value"><?= (int) $metricas['para_hoy'] ?></p></div><span class="app-stat-card__icon"><i class="fa-solid fa-calendar-day"></i></span></div></div></div>
        <div class="col-12 col-lg-4"><div class="app-stat-card app-stat-card--success"><div class="app-stat-card__body"><div><p class="app-stat-card__label">Ventas completadas</p><p class="app-stat-card__value">L. <?= number_format((float) $metricas['ventas'], 2) ?></p></div><span class="app-stat-card__icon"><i class="fa-solid fa-money-bill-trend-up"></i></span></div></div></div>
    </div>

    <?php if ($productos_caducados !== []): ?>
    <section class="expired-strip mb-4" aria-labelledby="expiredTitle">
        <span class="expired-strip__icon"><i class="fa-solid fa-triangle-exclamation"></i></span>
        <div><h5 id="expiredTitle">Hay <?= count($productos_caducados) ?> lote(s) caducado(s)</h5><p>Retíralos del inventario para evitar su venta.</p></div>
        <button class="btn btn-danger" type="button" data-bs-toggle="modal" data-bs-target="#expiredInventoryModal">Revisar lotes</button>
    </section>
    <?php endif; ?>

    <section class="inventory-alerts mb-4" aria-labelledby="inventoryAlertsTitle">
        <header class="inventory-alerts__heading">
            <div><h5 id="inventoryAlertsTitle">Estado del inventario</h5><p>Selecciona una categoría para revisar el detalle.</p></div>
        </header>
        <div class="inventory-alert-grid">
            <?php foreach ($inventoryCards as $card): ?>
            <button type="button" class="inventory-alert-card inventory-alert-card--<?= $card['variant'] ?>" data-bs-toggle="modal" data-bs-target="#<?= $card['modal'] ?>">
                <span class="inventory-alert-card__icon"><i class="fa-solid <?= $card['icon'] ?>"></i></span>
                <span class="inventory-alert-card__body"><strong><?= e($card['title']) ?></strong><small><?= e($card['description']) ?></small></span>
                <span class="inventory-alert-card__count"><?= count($card['items']) ?></span>
                <span class="inventory-alert-card__action">Ver detalle <i class="fa-solid fa-arrow-right"></i></span>
            </button>
            <?php endforeach; ?>
        </div>
    </section>

    <div class="quick-actions">
        <h6>Acciones rápidas</h6>
        <div>
            <a href="index.php?view=pedidos-nuevo" class="btn btn-white shadow-sm border fw-semibold"><i class="fa-solid fa-plus text-primary me-2"></i>Nuevo pedido</a>
            <a href="index.php?view=inventario" class="btn btn-white shadow-sm border fw-semibold"><i class="fa-solid fa-boxes-stacked text-primary me-2"></i>Ver inventario</a>
        </div>
    </div>
</div>

<?php foreach ($inventoryCards as $card): ?>
<div class="modal fade inventory-detail-modal inventory-detail-modal--<?= $card['variant'] ?>" id="<?= $card['modal'] ?>" tabindex="-1" aria-labelledby="<?= $card['modal'] ?>Title" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down">
        <div class="modal-content">
            <header class="modal-header">
                <div class="inventory-detail-modal__title"><span class="inventory-alert-card__icon"><i class="fa-solid <?= $card['icon'] ?>"></i></span><div><small>Inventario</small><h5 class="modal-title" id="<?= $card['modal'] ?>Title"><?= e($card['title']) ?></h5></div></div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </header>
            <div class="modal-body">
                <?php if ($card['items'] === []): ?>
                <div class="inventory-detail-empty"><i class="fa-solid fa-circle-check"></i><strong>Todo en orden</strong><span>No hay productos en esta categoría.</span></div>
                <?php else: ?>
                <div class="inventory-detail-list">
                    <?php foreach ($card['items'] as $item): ?>
                    <article class="inventory-detail-item">
                        <div class="inventory-detail-item__main"><strong><?= e($item['nombre_producto']) ?></strong><span><i class="fa-solid fa-store"></i><?= e($item['nombre_sucursal']) ?></span></div>
                        <?php if ($card['variant'] === 'expiry'): ?>
                            <div class="inventory-detail-item__metric"><small>Existencia</small><strong><?= (int) $item['stock_actual'] ?> unid.</strong></div>
                            <div class="inventory-detail-item__metric"><small>Vence</small><strong><?= e(date('d/m/Y', strtotime($item['fecha_caducidad']))) ?></strong></div>
                        <?php else: ?>
                            <div class="inventory-detail-item__metric"><small>Existencia</small><strong><?= (int) $item['stock_actual'] <= 0 ? 'Agotado' : (int) $item['stock_actual'] . ' unid.' ?></strong></div>
                            <div class="inventory-detail-item__action"><?php include __DIR__ . '/partials/critical-stock-button.php'; ?></div>
                        <?php endif; ?>
                    </article>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <footer class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button><a class="btn btn-primary" href="index.php?view=inventario"><i class="fa-solid fa-boxes-stacked me-2"></i>Ir a inventario</a></footer>
        </div>
    </div>
</div>
<?php endforeach; ?>

<?php if ($productos_caducados !== []): ?>
<div class="modal fade inventory-detail-modal inventory-detail-modal--danger" id="expiredInventoryModal" tabindex="-1" aria-labelledby="expiredInventoryModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down"><div class="modal-content">
        <header class="modal-header"><div class="inventory-detail-modal__title"><span class="inventory-alert-card__icon"><i class="fa-solid fa-triangle-exclamation"></i></span><div><small>Acción requerida</small><h5 class="modal-title" id="expiredInventoryModalTitle">Lotes caducados</h5></div></div><button type="button" class="btn-close" data-bs-dismiss="modal"></button></header>
        <div class="modal-body"><div class="inventory-detail-list">
            <?php foreach ($productos_caducados as $item): ?>
            <article class="inventory-detail-item"><div class="inventory-detail-item__main"><strong><?= e($item['nombre_producto']) ?></strong><span><i class="fa-solid fa-store"></i><?= e($item['nombre_sucursal']) ?></span></div><div class="inventory-detail-item__metric"><small>Venció</small><strong><?= e(date('d/m/Y', strtotime($item['fecha_caducidad']))) ?></strong></div><?php if (\App\Security\Auth::canAccessBranch((int) $item['id_sucursal'], 'inventory.waste')): ?><button type="button" class="btn btn-sm btn-danger btn-mermar-caducado" data-id="<?= (int) $item['id_inventario'] ?>" data-nombre="<?= e($item['nombre_producto']) ?>"><i class="fa-solid fa-trash-can me-1"></i>Registrar merma</button><?php endif; ?></article>
            <?php endforeach; ?>
        </div></div>
        <footer class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button></footer>
    </div></div>
</div>
<?php endif; ?>

<?php
$restockProducts = array_merge($inventario_agotado, $inventario_stock_bajo);
$restockDefaultBranch = 0;
if (\App\Security\Auth::hasPermission('inventory.adjust')) include __DIR__ . '/../inventario/partials/restock-panel.php';
?>

<link rel="stylesheet" href="css/views/dashboard.css?v=<?= filemtime(__DIR__ . '/../../public/css/views/dashboard.css') ?>">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.26.25"></script>
<script src="js/views/dashboard.js?v=<?= filemtime(__DIR__ . '/../../public/js/views/dashboard.js') ?>"></script>
