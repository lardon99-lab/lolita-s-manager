<?php
require_once '../app/controllers/InventarioController.php';

$invCtrl = new InventarioController();

$id_sucursal_user = (int) ($_SESSION['id_sucursal'] ?? 0) ?: null;
$requestedBranch = !empty($_GET['sucursal_id']) ? (int) $_GET['sucursal_id'] : null;
if ($requestedBranch && \App\Security\Auth::canAccessBranch($requestedBranch, 'sales.create')) $id_sucursal_user = $requestedBranch;
$allowedBranches = \App\Security\Auth::allowedBranches('sales.create');
$db = (new Database())->getConnection();
if ($allowedBranches === null) {
    $sucursales = $db->query("SELECT id_sucursal, nombre_sucursal FROM sucursales WHERE estado = 'Activa' ORDER BY nombre_sucursal")->fetchAll(PDO::FETCH_ASSOC);
} elseif ($allowedBranches === []) {
    $sucursales = [];
} else {
    $holders = implode(',', array_fill(0, count($allowedBranches), '?'));
    $stmtBranches = $db->prepare("SELECT id_sucursal, nombre_sucursal FROM sucursales WHERE estado = 'Activa' AND id_sucursal IN ($holders) ORDER BY nombre_sucursal");
    $stmtBranches->execute($allowedBranches);
    $sucursales = $stmtBranches->fetchAll(PDO::FETCH_ASSOC);
}
if ($id_sucursal_user === null && count($sucursales) === 1) $id_sucursal_user = (int) $sucursales[0]['id_sucursal'];
$productos = $id_sucursal_user ? $invCtrl->listarProductosDisponibles($id_sucursal_user) : [];
$configuraciones = (new \App\Services\ProductCustomizationService($db))
    ->configurationsForProducts(array_column($productos, 'id_producto'));
?>

<div class="container-fluid app-page sales-page p-2 p-md-4">
    <header class="page-shell sales-page__header p-3 p-md-4 mb-3">
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-3">
                <span class="sales-page__icon" aria-hidden="true"><i class="fa-solid fa-cart-shopping"></i></span>
                <div>
                    <h2 class="page-title fw-bold mb-1">Registrar venta</h2>
                    <p class="page-subtitle mb-0">Selecciona productos, revisa el carrito y finaliza el cobro.</p>
                </div>
            </div>
            <span class="sales-page__mode"><i class="fa-solid fa-bolt" aria-hidden="true"></i> Venta directa</span>
        </div>
    </header>

    <?php if (count($sucursales) > 1 || empty($_SESSION['id_sucursal'])): ?>
        <section class="sales-branch mb-3" aria-labelledby="salesBranchLabel">
            <label id="salesBranchLabel" for="salesBranch" class="sales-field-label">
                <i class="fa-solid fa-store" aria-hidden="true"></i> Sucursal de despacho
            </label>
            <div class="sales-branch__control">
                <select id="salesBranch" class="form-select" onchange="location.href='index.php?view=ventas-nueva&sucursal_id=' + encodeURIComponent(this.value)">
                    <option value="">-- Seleccione sucursal --</option>
                    <?php foreach($sucursales as $s): ?>
                        <option value="<?= $s['id_sucursal'] ?>" <?= ($id_sucursal_user == $s['id_sucursal']) ? 'selected' : '' ?>>
                            <?= e($s['nombre_sucursal']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </section>
    <?php endif; ?>

    <?php if (!$id_sucursal_user): ?>
        <section class="sales-empty-state">
            <i class="fa-solid fa-store" aria-hidden="true"></i>
            <h5>Selecciona una sucursal</h5>
            <p>El inventario disponible aparecerá aquí.</p>
        </section>
    <?php else: ?>
        <form id="formNuevaVenta">
            <input type="hidden" name="id_sucursal" id="id_sucursal" value="<?= $id_sucursal_user ?>">

            <div class="sales-workspace">
                <section class="sales-catalog" aria-labelledby="salesCatalogTitle">
                    <div class="sales-panel-heading">
                        <div>
                            <span class="sales-panel-heading__step">1</span>
                            <h3 id="salesCatalogTitle">Productos</h3>
                        </div>
                        <span>Stock disponible</span>
                    </div>

                    <label class="sales-field-label" for="buscador-producto">Buscar producto</label>
                    <div class="sales-search">
                        <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                        <input type="search" id="buscador-producto" class="form-control" placeholder="Escriba el nombre..." autocomplete="off">
                    </div>

                    <select name="id_producto" id="id_producto" class="form-select custom-select-list" size="4" aria-label="Productos disponibles" required>
                        <?php if (empty($productos)): ?>
                            <option value="" disabled>Sin stock disponible en esta sucursal</option>
                        <?php else: ?>
                            <?php foreach($productos as $p): ?>
                                <option value="<?= $p['id_producto'] ?>"
                                        data-precio="<?= $p['precio_base'] ?>"
                                        data-stock="<?= $p['stock'] ?>"
                                        data-stock-key="<?= e($p['stock_key'] ?? ('producto:' . $p['id_producto'])) ?>"
                                        data-tipo="<?= e($p['tipo_producto'] ?? 'panaderia') ?>">
                                    <?= e($p['nombre_producto']) ?> — (Stock: <?= (int) $p['stock'] ?>)
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>

                    <div id="no-results" class="alert alert-warning small mt-2 mb-0 py-2 d-none border-0">
                        <i class="fa-solid fa-circle-exclamation me-2" aria-hidden="true"></i>No hay coincidencias.
                    </div>

                    <div class="sales-add-row">
                        <div>
                            <label class="sales-field-label" for="cantidad">Cantidad</label>
                            <input type="number" id="cantidad" min="1" class="form-control" value="1" inputmode="numeric">
                        </div>
                        <div>
                            <span class="sales-field-label">Precio unitario</span>
                            <output id="label-precio" class="sales-unit-price">L. 0.00</output>
                        </div>
                        <button type="button" id="btnAgregarCarrito" class="btn btn-primary sales-add-button">
                            <i class="fa-solid fa-plus" aria-hidden="true"></i>
                            Agregar
                        </button>
                    </div>
                </section>

                <section class="sales-order" aria-labelledby="salesOrderTitle">
                    <div class="sales-panel-heading">
                        <div>
                            <span class="sales-panel-heading__step">2</span>
                            <h3 id="salesOrderTitle">Detalle de venta</h3>
                        </div>
                        <span class="sales-items-count" id="items-count">0 Items</span>
                    </div>

                    <div class="sales-cart-scroll">
                        <table class="table align-middle mb-0 sales-cart-table">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th class="text-center">Cant.</th>
                                    <th class="text-end">Precio</th>
                                    <th class="text-end">Subtotal</th>
                                    <th class="text-center"><span class="visually-hidden">Acciones</span></th>
                                </tr>
                            </thead>
                            <tbody id="cuerpoCarrito">
                                <tr class="sales-cart-empty">
                                    <td colspan="5">
                                        <i class="fa-solid fa-basket-shopping" aria-hidden="true"></i>
                                        <span>El carrito está vacío</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="sales-checkout">
                        <fieldset class="sales-payment">
                            <legend>Método de pago</legend>
                            <div class="sales-payment__options">
                                <input type="radio" class="btn-check" name="metodo_pago" id="pago_efectivo" value="Efectivo" checked>
                                <label for="pago_efectivo"><i class="fa-solid fa-money-bill-wave" aria-hidden="true"></i>Efectivo</label>

                                <input type="radio" class="btn-check" name="metodo_pago" id="pago_tarjeta" value="Tarjeta">
                                <label for="pago_tarjeta"><i class="fa-solid fa-credit-card" aria-hidden="true"></i>Tarjeta</label>
                            </div>
                        </fieldset>

                        <div class="sales-total">
                            <span>Total a pagar</span>
                            <output id="total-venta">L. 0.00</output>
                        </div>

                        <button type="button" id="btnCobrar" class="btn btn-success sales-checkout__button" disabled>
                            <i class="fa-solid fa-cash-register" aria-hidden="true"></i>
                            Finalizar cobro
                        </button>
                    </div>
                </section>
            </div>
        </form>
    <?php endif; ?>
</div>

<div class="modal fade sales-customization" id="salesCustomizationModal" tabindex="-1" aria-labelledby="salesCustomizationTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down">
        <div class="modal-content">
            <header class="modal-header">
                <div>
                    <span class="sales-customization__eyebrow">Personalizar producto</span>
                    <h2 class="modal-title" id="salesCustomizationTitle">Configurar</h2>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </header>
            <div class="modal-body">
                <div id="salesCustomizationGroups" class="sales-customization__groups"></div>
            </div>
            <footer class="modal-footer">
                <div class="sales-customization__price"><span>Precio unitario</span><strong id="salesCustomizationPrice">L. 0.00</strong></div>
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="salesCustomizationAdd"><i class="fa-solid fa-plus" aria-hidden="true"></i>Añadir al carrito</button>
            </footer>
        </div>
    </div>
</div>

<link rel="stylesheet" href="css/views/ventas-nueva.css?v=<?= filemtime(__DIR__ . '/../../public/css/views/ventas-nueva.css') ?>">

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.26.25"></script>
<script id="sales-product-configurations" type="application/json"><?= json_encode($configuraciones, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?></script>
<script src="js/views/ventas-nueva.js?v=<?= filemtime(__DIR__ . '/../../public/js/views/ventas-nueva.js') ?>"></script>
