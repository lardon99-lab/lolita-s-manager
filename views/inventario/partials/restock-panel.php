<?php
$restockCatalog = [];
foreach (($restockProducts ?? []) as $restockProduct) {
    $branchId = (int) ($restockProduct['id_sucursal'] ?? 0);
    $productId = (int) ($restockProduct['id_producto'] ?? 0);
    if ($branchId < 1 || $productId < 1 || ($restockProduct['estado'] ?? 'Activo') !== 'Activo'
        || !\App\Security\Auth::canAccessBranch($branchId, 'inventory.adjust')) continue;
    $key = $branchId . ':' . $productId;
    $restockCatalog[$key] = [
        'product_id' => $productId,
        'branch_id' => $branchId,
        'name' => (string) ($restockProduct['nombre_producto'] ?? ''),
        'branch_name' => (string) ($restockProduct['nombre_sucursal'] ?? ''),
        'stock' => (int) ($restockProduct['stock_actual'] ?? 0),
        'minimum' => (int) ($restockProduct['stock_minimo'] ?? 0),
        'shelf_life' => (int) ($restockProduct['dias_vida_util'] ?? 0),
    ];
}
$restockCatalog = array_values($restockCatalog);
$restockDefaultBranch = (int) ($restockDefaultBranch ?? 0);
?>

<?php if ($restockCatalog !== []): ?>
<div class="modal fade inventory-restock" id="inventoryRestockModal" tabindex="-1" aria-labelledby="inventoryRestockTitle" aria-hidden="true" data-default-branch="<?= $restockDefaultBranch ?>">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down">
        <div class="modal-content">
            <header class="modal-header inventory-restock__header">
                <div class="d-flex align-items-center gap-3 min-w-0">
                    <span class="inventory-restock__header-icon" aria-hidden="true"><i class="fa-solid fa-boxes-packing"></i></span>
                    <div class="min-w-0">
                        <h2 class="modal-title" id="inventoryRestockTitle">Abastecer inventario</h2>
                        <p class="mb-0">Registra una recepción completa en una sola operación.</p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </header>

            <form id="inventoryRestockForm" class="inventory-restock__form" novalidate>
                <div class="modal-body inventory-restock__body">
                    <div class="inventory-restock__layout">
                        <section class="inventory-restock__catalog" aria-labelledby="restockCatalogTitle">
                            <div class="inventory-restock__section-heading">
                                <div>
                                    <span class="inventory-restock__step">1</span>
                                    <h3 id="restockCatalogTitle">Productos</h3>
                                </div>
                                <small id="restockBranchName"></small>
                            </div>
                            <label class="inventory-restock__search" for="restockProductSearch">
                                <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                                <input id="restockProductSearch" type="search" class="form-control" placeholder="Buscar producto..." autocomplete="off">
                            </label>
                            <div id="restockProductCatalog" class="inventory-restock__product-list" role="list">
                                <?php foreach ($restockCatalog as $product): ?>
                                <button type="button"
                                        class="inventory-restock__product"
                                        data-restock-product="<?= $product['branch_id'] ?>:<?= $product['product_id'] ?>"
                                        data-restock-branch="<?= $product['branch_id'] ?>"
                                        data-search="<?= e(mb_strtolower($product['name'] . ' ' . $product['branch_name'])) ?>"
                                        role="listitem">
                                    <span>
                                        <strong><?= e($product['name']) ?></strong>
                                        <small><i class="fa-solid fa-store" aria-hidden="true"></i><?= e($product['branch_name']) ?></small>
                                    </span>
                                    <span class="inventory-restock__product-stock"><?= $product['stock'] ?> unid.</span>
                                    <i class="fa-solid fa-plus" aria-hidden="true"></i>
                                </button>
                                <?php endforeach; ?>
                            </div>
                            <p id="restockCatalogEmpty" class="inventory-restock__catalog-empty" hidden>No hay productos que coincidan.</p>
                        </section>

                        <section class="inventory-restock__receipt" aria-labelledby="restockReceiptTitle">
                            <div class="inventory-restock__section-heading">
                                <div>
                                    <span class="inventory-restock__step">2</span>
                                    <h3 id="restockReceiptTitle">Detalle de recepción</h3>
                                </div>
                                <span id="restockLineCount" class="inventory-restock__count">0 productos</span>
                            </div>
                            <div id="restockEmptyState" class="inventory-restock__empty">
                                <i class="fa-solid fa-box-open" aria-hidden="true"></i>
                                <strong>Aún no agregaste productos</strong>
                                <span>Selecciona uno en la lista para comenzar.</span>
                            </div>
                            <div id="restockLines" class="inventory-restock__lines" aria-live="polite"></div>
                            <div class="inventory-restock__notes">
                                <label for="restockNotes">Nota de recepción <span>(opcional)</span></label>
                                <textarea id="restockNotes" class="form-control" rows="2" maxlength="500" placeholder="Proveedor, factura u observación breve"></textarea>
                            </div>
                        </section>
                    </div>
                </div>
                <footer class="modal-footer inventory-restock__footer">
                    <div class="inventory-restock__summary" aria-live="polite">
                        <span>Unidades a ingresar</span>
                        <strong id="restockUnitCount">0</strong>
                    </div>
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button id="restockSubmit" type="submit" class="btn btn-success" disabled>
                        <i class="fa-solid fa-check" aria-hidden="true"></i>Confirmar abastecimiento
                    </button>
                </footer>
            </form>
        </div>
    </div>
</div>

<script id="inventoryRestockCatalogData" type="application/json"><?= json_encode($restockCatalog, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
<link rel="stylesheet" href="css/views/restock.css?v=<?= filemtime(__DIR__ . '/../../../public/css/views/restock.css') ?>">
<script src="js/components/inventory-restock.js?v=<?= filemtime(__DIR__ . '/../../../public/js/components/inventory-restock.js') ?>"></script>
<?php endif; ?>
