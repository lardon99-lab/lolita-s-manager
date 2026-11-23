<?php
$selectedProductName = null;
foreach ($reporte_productos as $reportProduct) {
    if ((int) $reportProduct['id_producto'] === (int) ($reportFilters['id_producto'] ?? 0)) {
        $selectedProductName = $reportProduct['nombre_producto'];
        break;
    }
}
$branchFilterVisible = count($reporte_sucursales) > 1;
?>

<link rel="stylesheet" href="css/views/reporte-productos.css?v=<?= filemtime(__DIR__ . '/../../public/css/views/reporte-productos.css') ?>">

<main class="container-fluid app-page p-2 p-md-4 product-sales-page">
    <header class="app-page-header product-sales-header">
        <div class="app-page-header__main">
            <span class="app-page-header__icon" aria-hidden="true"><i class="fa-solid fa-chart-column"></i></span>
            <div class="app-page-header__copy min-w-0">
                <h1 class="app-page-header__title">Reporte de ventas</h1>
                <p class="app-page-header__subtitle">Compara unidades e ingresos por producto en el periodo seleccionado.</p>
            </div>
        </div>
        <a class="btn btn-outline-primary product-sales-history-link" href="index.php?view=ventas-historial">
            <i class="fa-solid fa-clock-rotate-left me-2" aria-hidden="true"></i>Ver historial
        </a>
    </header>

    <section class="card app-panel product-sales-filter-panel" aria-labelledby="productSalesFiltersTitle">
        <div class="card-body p-3 p-md-4">
            <div class="product-sales-section-heading">
                <div>
                    <h2 id="productSalesFiltersTitle">Filtros del reporte</h2>
                    <p>Sin elegir un producto se muestran los más vendidos.</p>
                </div>
            </div>
            <form method="get" action="index.php" class="product-sales-filters <?= $branchFilterVisible ? 'product-sales-filters--with-branch' : 'product-sales-filters--without-branch' ?>">
                <input type="hidden" name="view" value="reporte-productos">
                <div class="product-sales-filter-field">
                    <label class="form-label" for="salesReportFrom">Desde</label>
                    <input class="form-control" id="salesReportFrom" type="date" name="desde" value="<?= e($reportFilters['desde']) ?>" max="<?= e($reportFilters['hasta']) ?>" data-app-date data-date-peer="#salesReportTo" data-date-boundary="min" required>
                </div>
                <div class="product-sales-filter-field">
                    <label class="form-label" for="salesReportTo">Hasta</label>
                    <input class="form-control" id="salesReportTo" type="date" name="hasta" value="<?= e($reportFilters['hasta']) ?>" min="<?= e($reportFilters['desde']) ?>" max="<?= e($today) ?>" data-app-date data-date-peer="#salesReportFrom" data-date-boundary="max" required>
                </div>
                <?php if ($branchFilterVisible): ?>
                <div class="product-sales-filter-field">
                    <label class="form-label" for="salesReportBranch">Sucursal</label>
                    <select class="form-select" id="salesReportBranch" name="sucursal">
                        <option value="">Todas mis sucursales</option>
                        <?php foreach ($reporte_sucursales as $branch): ?>
                        <option value="<?= (int) $branch['id_sucursal'] ?>" <?= (int) ($reportFilters['sucursal'] ?? 0) === (int) $branch['id_sucursal'] ? 'selected' : '' ?>><?= e($branch['nombre_sucursal']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                <div class="product-sales-filter-field product-sales-filter-field--product">
                    <label class="form-label" for="salesReportProduct">Producto</label>
                    <select class="form-select" id="salesReportProduct" name="id_producto">
                        <option value="">Todos los productos</option>
                        <?php foreach ($reporte_productos as $product): ?>
                        <option value="<?= (int) $product['id_producto'] ?>" <?= (int) ($reportFilters['id_producto'] ?? 0) === (int) $product['id_producto'] ? 'selected' : '' ?>><?= e($product['nombre_producto']) ?><?= $product['estado'] === 'Inactivo' ? ' (Inactivo)' : '' ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="product-sales-filter-actions">
                    <button class="btn btn-primary" type="submit"><i class="fa-solid fa-filter" aria-hidden="true"></i><span>Generar reporte</span></button>
                    <a class="btn btn-outline-secondary" href="index.php?view=reporte-productos" aria-label="Limpiar filtros"><i class="fa-solid fa-rotate-left" aria-hidden="true"></i><span>Limpiar</span></a>
                </div>
            </form>
        </div>
    </section>

    <?php if ($reporte_acceso_denegado): ?>
    <div class="alert alert-danger" role="alert">No tienes acceso a la sucursal seleccionada.</div>
    <?php else: ?>
    <section class="product-sales-summary" aria-label="Resumen del periodo">
        <article class="card app-panel product-sales-stat">
            <span class="product-sales-stat__icon product-sales-stat__icon--pink" aria-hidden="true"><i class="fa-solid fa-boxes-stacked"></i></span>
            <div><span>Unidades vendidas</span><strong><?= number_format((int) $reporte_resumen['unidades']) ?></strong></div>
        </article>
        <article class="card app-panel product-sales-stat">
            <span class="product-sales-stat__icon product-sales-stat__icon--green" aria-hidden="true"><i class="fa-solid fa-money-bill-wave"></i></span>
            <div><span>Importe de ventas</span><strong>L. <?= number_format((float) $reporte_resumen['ventas'], 2) ?></strong></div>
        </article>
        <article class="card app-panel product-sales-stat">
            <span class="product-sales-stat__icon product-sales-stat__icon--yellow" aria-hidden="true"><i class="fa-solid fa-receipt"></i></span>
            <div><span><?= e($reporte_resumen['tercer_indicador']['etiqueta']) ?></span><strong><?= number_format((int) $reporte_resumen['tercer_indicador']['valor']) ?></strong></div>
        </article>
    </section>

    <section class="card app-panel overflow-hidden product-sales-results" aria-labelledby="productSalesResultsTitle">
        <div class="product-sales-results__heading">
            <div>
                <h2 id="productSalesResultsTitle"><?= $selectedProductName !== null ? 'Detalle por sucursal' : 'Productos más vendidos' ?></h2>
                <p>
                    <?php if ($selectedProductName !== null): ?>
                        <?= e($selectedProductName) ?> · <?= e($reportFilters['desde']) ?> al <?= e($reportFilters['hasta']) ?>
                    <?php else: ?>
                        Ranking por unidades · <?= e($reportFilters['desde']) ?> al <?= e($reportFilters['hasta']) ?>
                    <?php endif; ?>
                </p>
            </div>
            <?php if ($selectedProductName !== null): ?><span class="product-sales-mode"><i class="fa-solid fa-filter" aria-hidden="true"></i> Producto seleccionado</span><?php endif; ?>
        </div>

        <?php if ($selectedProductName === null): ?>
        <div class="table-responsive">
            <table class="table align-middle mb-0 product-sales-table">
                <thead><tr><th>Pos.</th><th>Producto</th><th class="text-end">Unidades</th><th class="text-end">Transacciones</th><th class="text-end">Importe vendido</th></tr></thead>
                <tbody>
                    <?php if ($reporte_filas === []): ?>
                    <tr><td colspan="5" class="product-sales-empty">No hay ventas de productos en este periodo.</td></tr>
                    <?php else: foreach ($reporte_filas as $index => $row): ?>
                    <tr>
                        <td data-label="Posición"><span class="product-sales-rank"><?= $index + 1 ?></span></td>
                        <td data-label="Producto"><strong><?= e($row['nombre_producto']) ?></strong></td>
                        <td data-label="Unidades" class="text-end fw-bold"><?= number_format((int) $row['unidades']) ?></td>
                        <td data-label="Transacciones" class="text-end"><?= number_format((int) $row['transacciones']) ?></td>
                        <td data-label="Importe vendido" class="text-end product-sales-amount">L. <?= number_format((float) $row['total_ventas'], 2) ?></td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table align-middle mb-0 product-sales-table">
                <thead><tr><th>Sucursal</th><th class="text-end">Unidades</th><th class="text-end">Transacciones</th><th class="text-end">Importe vendido</th></tr></thead>
                <tbody>
                    <?php if ($reporte_filas === []): ?>
                    <tr><td colspan="4" class="product-sales-empty">Este producto no registra ventas en el periodo seleccionado.</td></tr>
                    <?php else: foreach ($reporte_filas as $row): ?>
                    <tr>
                        <td data-label="Sucursal"><strong><?= e($row['nombre_sucursal']) ?></strong></td>
                        <td data-label="Unidades" class="text-end fw-bold"><?= number_format((int) $row['unidades']) ?></td>
                        <td data-label="Transacciones" class="text-end"><?= number_format((int) $row['transacciones']) ?></td>
                        <td data-label="Importe vendido" class="text-end product-sales-amount">L. <?= number_format((float) $row['total_ventas'], 2) ?></td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </section>
    <?php endif; ?>

    <p class="product-sales-note"><i class="fa-solid fa-circle-info me-1" aria-hidden="true"></i>Incluye ventas directas y pedidos entregados. Los importes son subtotales de productos, no el total de efectivo recibido.</p>
</main>
