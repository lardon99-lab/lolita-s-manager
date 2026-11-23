<?php
$canManageCategories = \App\Security\Auth::hasPermission('categories.manage');
$canManageSupplies = \App\Security\Auth::hasPermission('products.manage');
$catalogTabCount = 1 + (int) $canManageCategories + (int) $canManageSupplies;
?>

<div class="container-fluid app-page p-2 p-md-4 management-page catalog-page">
    <header class="app-page-header catalog-header">
        <div class="app-page-header__main">
            <span class="app-page-header__icon" aria-hidden="true"><i class="fa-solid fa-tags"></i></span>
            <div class="app-page-header__copy min-w-0">
            <h2 class="app-page-header__title">Catálogo</h2>
            <p class="app-page-header__subtitle">Administra los productos y tipos de vaso compartidos por tus sucursales.</p>
            </div>
        </div>
        <div class="app-page-header__actions catalog-header__actions">
            <?php if ($canManageSupplies): ?>
            <button class="btn btn-outline-primary catalog-header__action" type="button" data-bs-toggle="modal" data-bs-target="#newSupplyModal"><i class="fa-solid fa-glass-water me-2" aria-hidden="true"></i>Nuevo tipo de vaso</button>
            <?php endif; ?>
            <a class="btn btn-primary catalog-header__action" href="index.php?view=inventario"><i class="fa-solid fa-plus me-2" aria-hidden="true"></i>Nuevo producto</a>
        </div>
    </header>

    <nav class="inventory-tabs catalog-tabs catalog-tabs--<?= $catalogTabCount ?>" aria-label="Secciones del catálogo">
        <div class="nav nav-pills" role="tablist">
            <button class="nav-link active" id="productsTabButton" data-bs-toggle="tab" data-bs-target="#productsTab" type="button" role="tab" aria-controls="productsTab" aria-selected="true">
                <i class="fa-solid fa-boxes-stacked" aria-hidden="true"></i><span>Productos</span><span class="inventory-tabs__count"><?= count($productos) ?></span>
            </button>
            <?php if ($canManageSupplies): ?>
            <button class="nav-link" id="suppliesTabButton" data-bs-toggle="tab" data-bs-target="#suppliesTab" type="button" role="tab" aria-controls="suppliesTab" aria-selected="false">
                <i class="fa-solid fa-glass-water" aria-hidden="true"></i><span>Vasos e insumos</span><span class="inventory-tabs__count"><?= count($vasos_catalogo) ?></span>
            </button>
            <?php endif; ?>
            <?php if ($canManageCategories): ?>
            <button class="nav-link" id="categoriesTabButton" data-bs-toggle="tab" data-bs-target="#categoriesTab" type="button" role="tab" aria-controls="categoriesTab" aria-selected="false">
                <i class="fa-solid fa-tags" aria-hidden="true"></i><span>Categorías</span><span class="inventory-tabs__count"><?= count($categorias) ?></span>
            </button>
            <?php endif; ?>
        </div>
    </nav>

    <div class="tab-content inventory-tab-content catalog-tab-content">
        <section class="tab-pane fade show active" id="productsTab" role="tabpanel">
            <div class="card app-panel overflow-hidden">
                <div class="card-body p-3 p-md-4">
                    <div class="catalog-section-heading mb-3">
                        <div><h5>Productos del catálogo</h5><p>Consulta y administra los productos disponibles en tus sucursales.</p></div>
                        <span class="catalog-context-chip"><i class="fa-solid fa-layer-group" aria-hidden="true"></i> Catálogo general</span>
                    </div>
                    <div class="catalog-toolbar mb-3">
                        <div class="app-search-field"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i><input type="search" class="form-control" data-list-search="#productsTab" data-search-status="#catalogSearchStatus" placeholder="Buscar producto, categoría o sucursal" aria-label="Buscar productos"></div>
                        <small class="catalog-result-count" id="catalogSearchStatus"><?= count($productos) ?> resultados</small>
                    </div>
            <div class="table-responsive app-panel d-none d-md-block">
                <table class="table table-hover align-middle mb-0 management-table">
                    <thead><tr><th>Producto</th><th>Categoria</th><th>Precio</th><th>Sucursales</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead>
                    <tbody>
                    <?php foreach ($productos as $producto): ?>
                        <?php $productJson = e(json_encode($producto, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP)); ?>
                        <tr data-search-item>
                            <td><div class="fw-semibold"><?= e($producto['nombre_producto']) ?></div><?php if ((int) $producto['grupos_personalizacion'] > 0): ?><div class="small text-success mt-1"><i class="fa-solid fa-sliders me-1"></i><?= (int) $producto['grupos_personalizacion'] ?> grupos</div><?php endif; ?></td>
                            <td><span class="catalog-product-category"><?= e($producto['nombre_categoria']) ?></span></td>
                            <td class="catalog-product-price">L. <?= number_format((float) $producto['precio_base'], 2) ?></td>
                            <td><?= e($producto['sucursales'] ?: 'Sin inventario') ?></td>
                            <td><span class="badge catalog-product-state <?= $producto['estado'] === 'Activo' ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= e($producto['estado']) ?></span></td>
                            <td class="text-end text-nowrap">
                                <button class="btn btn-sm btn-outline-primary js-edit-product" type="button" title="Editar" data-product='<?= $productJson ?>'><i class="fa-solid fa-pen"></i></button>
                                <button class="btn btn-sm btn-outline-success js-customize-product" type="button" title="Personalizaciones" data-id="<?= (int) $producto['id_producto'] ?>" data-name="<?= e($producto['nombre_producto']) ?>"><i class="fa-solid fa-sliders"></i><span class="visually-hidden">Configurar personalizaciones</span></button>
                                <button class="btn btn-sm btn-outline-secondary js-state-product" type="button" title="Cambiar estado" data-id="<?= (int) $producto['id_producto'] ?>" data-state="<?= $producto['estado'] === 'Activo' ? 'Inactivo' : 'Activo' ?>"><i class="fa-solid fa-power-off"></i></button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="catalog-mobile-list d-md-none">
                <?php if ($productos === []): ?><p class="catalog-empty">No hay productos disponibles.</p><?php endif; ?>
                <?php foreach ($productos as $producto): ?>
                    <?php $productJson = e(json_encode($producto, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP)); ?>
                    <article class="catalog-mobile-item" data-search-item>
                        <div class="catalog-mobile-item__header">
                            <h3><?= e($producto['nombre_producto']) ?></h3>
                            <span class="badge <?= $producto['estado'] === 'Activo' ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= e($producto['estado']) ?></span>
                        </div>
                        <dl class="catalog-mobile-item__details">
                            <div><dt>Categoria</dt><dd><?= e($producto['nombre_categoria']) ?></dd></div>
                            <div><dt>Precio</dt><dd>L. <?= number_format((float) $producto['precio_base'], 2) ?></dd></div>
                            <div><dt>Sucursales</dt><dd><?= e($producto['sucursales'] ?: 'Sin inventario') ?></dd></div>
                            <div><dt>Opciones</dt><dd><?= (int) $producto['grupos_personalizacion'] ?> grupos configurados</dd></div>
                        </dl>
                        <div class="catalog-mobile-item__actions">
                            <button class="btn btn-outline-primary js-edit-product" type="button" data-product='<?= $productJson ?>'><i class="fa-solid fa-pen me-2"></i>Editar</button>
                            <button class="btn btn-outline-success js-customize-product" type="button" data-id="<?= (int) $producto['id_producto'] ?>" data-name="<?= e($producto['nombre_producto']) ?>"><i class="fa-solid fa-sliders me-2"></i>Opciones</button>
                            <button class="btn btn-outline-secondary js-state-product" type="button" data-id="<?= (int) $producto['id_producto'] ?>" data-state="<?= $producto['estado'] === 'Activo' ? 'Inactivo' : 'Activo' ?>"><i class="fa-solid fa-power-off me-2"></i><?= $producto['estado'] === 'Activo' ? 'Desactivar' : 'Activar' ?></button>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
                </div>
            </div>
        </section>

        <?php if ($canManageSupplies): ?>
        <section class="tab-pane fade" id="suppliesTab" role="tabpanel" aria-labelledby="suppliesTabButton" tabindex="0">
            <div class="card app-panel overflow-hidden">
            <div class="card-body p-3 p-md-4">
            <div class="catalog-section-heading catalog-supply-toolbar">
                <div>
                    <h3>Vasos e insumos compartidos</h3>
                    <p>Administra los tipos disponibles, su uso y su estado. Los cambios de catálogo aplican a todas las sucursales.</p>
                </div>
                <div class="app-search-field catalog-supply-toolbar__search">
                    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                    <input class="form-control" type="search" data-list-search="#suppliesTab" data-search-status="#supplyCatalogSearchStatus" placeholder="Buscar vaso o tipo de uso" aria-label="Buscar vasos e insumos">
                </div>
            </div>
            <div class="catalog-supply-statusline"><small id="supplyCatalogSearchStatus"><?= count($vasos_catalogo) ?> resultados</small></div>
            <div class="catalog-supply-grid">
                <?php foreach ($vasos_catalogo as $supply): ?>
                <?php $supplyJson = e(json_encode($supply, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP)); ?>
                <article class="catalog-supply-card <?= $supply['estado'] === 'Inactivo' ? 'is-inactive' : '' ?>" data-search-item>
                    <div class="catalog-supply-card__heading">
                        <span class="catalog-supply-card__icon"><i class="fa-solid fa-glass-water" aria-hidden="true"></i></span>
                        <div class="catalog-supply-card__identity">
                            <h3><?= e($supply['nombre']) ?></h3>
                            <span><?= $supply['tipo_uso'] === 'batido' ? 'Batidos' : 'Bebidas' ?></span>
                        </div>
                        <span class="badge <?= $supply['estado'] === 'Activo' ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= e($supply['estado']) ?></span>
                    </div>
                    <dl class="catalog-supply-card__details">
                        <div><dt>Stock total</dt><dd><?= rtrim(rtrim(number_format((float) $supply['stock_total'], 3, '.', ''), '0'), '.') ?> unidades</dd></div>
                        <div><dt>Stock mínimo</dt><dd><?= (int) $supply['stock_minimo'] ?> por sucursal</dd></div>
                        <div><dt>Productos vinculados</dt><dd><?= (int) $supply['productos_vinculados'] ?></dd></div>
                    </dl>
                    <div class="catalog-supply-card__actions">
                        <button type="button" class="btn btn-outline-primary js-edit-supply" data-supply='<?= $supplyJson ?>'>
                            <i class="fa-solid fa-pen me-2" aria-hidden="true"></i>Editar
                        </button>
                        <button type="button" class="btn btn-outline-secondary js-state-supply" data-id="<?= (int) $supply['id_insumo'] ?>" data-name="<?= e($supply['nombre']) ?>" data-state="<?= $supply['estado'] === 'Activo' ? 'Inactivo' : 'Activo' ?>">
                            <i class="fa-solid <?= $supply['estado'] === 'Activo' ? 'fa-power-off' : 'fa-rotate-left' ?> me-2" aria-hidden="true"></i><?= $supply['estado'] === 'Activo' ? 'Desactivar' : 'Reactivar' ?>
                        </button>
                        <?php if ($supply['estado'] === 'Inactivo'): ?>
                        <button type="button" class="btn btn-outline-danger js-delete-supply" data-id="<?= (int) $supply['id_insumo'] ?>" data-name="<?= e($supply['nombre']) ?>">
                            <i class="fa-solid fa-trash-can me-2" aria-hidden="true"></i>Eliminar
                        </button>
                        <?php endif; ?>
                    </div>
                </article>
                <?php endforeach; ?>
                <?php if ($vasos_catalogo === []): ?>
                <p class="catalog-empty catalog-supply-grid__empty">No hay tipos de vaso registrados.</p>
                <?php endif; ?>
            </div>
            </div>
            </div>
        </section>
        <?php endif; ?>

        <?php if ($canManageCategories): ?>
        <section class="tab-pane fade" id="categoriesTab" role="tabpanel">
            <div class="card app-panel overflow-hidden">
            <div class="card-body p-3 p-md-4">
            <div class="catalog-section-heading mb-3">
                <div><h5>Categorías del catálogo</h5><p>Organiza los productos con las categorías disponibles.</p></div>
                <button class="btn btn-primary catalog-category-add" type="button" data-bs-toggle="modal" data-bs-target="#categoryModal"><i class="fa-solid fa-plus me-2" aria-hidden="true"></i>Nueva categoría</button>
            </div>
            <div class="table-responsive app-panel d-none d-md-block">
                <table class="table table-hover align-middle mb-0 management-table">
                    <thead><tr><th>Categoria</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead>
                    <tbody><?php foreach ($categorias as $categoria): ?><tr>
                        <td class="fw-semibold"><?= e($categoria['nombre_categoria']) ?></td>
                        <td><span class="badge <?= $categoria['estado'] === 'Activo' ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= e($categoria['estado']) ?></span></td>
                        <td class="text-end text-nowrap">
                            <button class="btn btn-sm btn-outline-primary js-edit-category" type="button" data-id="<?= (int) $categoria['id_categoria'] ?>" data-name="<?= e($categoria['nombre_categoria']) ?>" title="Editar"><i class="fa-solid fa-pen"></i></button>
                            <button class="btn btn-sm btn-outline-secondary js-state-category" type="button" data-id="<?= (int) $categoria['id_categoria'] ?>" data-state="<?= $categoria['estado'] === 'Activo' ? 'Inactivo' : 'Activo' ?>" title="Cambiar estado"><i class="fa-solid fa-power-off"></i></button>
                        </td>
                    </tr><?php endforeach; ?></tbody>
                </table>
            </div>
            <div class="catalog-mobile-list d-md-none">
                <?php foreach ($categorias as $categoria): ?><article class="catalog-mobile-item catalog-mobile-item--compact">
                    <div class="catalog-mobile-item__header"><h3><?= e($categoria['nombre_categoria']) ?></h3><span class="badge <?= $categoria['estado'] === 'Activo' ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= e($categoria['estado']) ?></span></div>
                    <div class="catalog-mobile-item__actions">
                        <button class="btn btn-outline-primary js-edit-category" type="button" data-id="<?= (int) $categoria['id_categoria'] ?>" data-name="<?= e($categoria['nombre_categoria']) ?>"><i class="fa-solid fa-pen me-2"></i>Editar</button>
                        <button class="btn btn-outline-secondary js-state-category" type="button" data-id="<?= (int) $categoria['id_categoria'] ?>" data-state="<?= $categoria['estado'] === 'Activo' ? 'Inactivo' : 'Activo' ?>"><i class="fa-solid fa-power-off me-2"></i><?= $categoria['estado'] === 'Activo' ? 'Desactivar' : 'Activar' ?></button>
                    </div>
                </article><?php endforeach; ?>
            </div>
            </div>
            </div>
        </section>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="customizationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down">
        <div class="modal-content">
            <form id="customizationForm">
                <div class="modal-header">
                    <div class="min-w-0">
                        <h5 class="modal-title">Personalizaciones</h5>
                        <div class="small text-muted text-truncate" id="customizationProductName"></div>
                    </div>
                    <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id_producto" id="customizationProductId">
                    <div id="customizationGroups" class="customization-groups"></div>
                    <button class="btn btn-outline-primary w-100 mt-3" id="addCustomizationGroup" type="button"><i class="fa-solid fa-plus me-2"></i>Agregar grupo</button>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-light" type="button" data-bs-dismiss="modal">Cancelar</button>
                    <button class="btn btn-primary" type="submit"><i class="fa-solid fa-floppy-disk me-2"></i>Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="productModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down"><div class="modal-content"><form id="productForm">
        <div class="modal-header"><h5 class="modal-title">Editar producto</h5><button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
        <div class="modal-body">
            <input type="hidden" name="id_producto" id="productId">
            <div class="mb-3"><label class="form-label" for="productName">Nombre</label><input class="form-control" name="nombre_producto" id="productName" maxlength="100" required></div>
            <div class="mb-3"><label class="form-label" for="productCategory">Categoria</label><select class="form-select" name="id_categoria" id="productCategory" required><?php foreach ($categorias as $categoria): if ($categoria['estado'] !== 'Activo') continue; ?><option value="<?= (int) $categoria['id_categoria'] ?>"><?= e($categoria['nombre_categoria']) ?></option><?php endforeach; ?></select></div>
            <div class="row g-3 mb-3"><div class="col-12 col-sm-6"><label class="form-label" for="productPrice">Precio</label><input class="form-control" name="precio_base" id="productPrice" type="number" min="0" max="1000000" step="0.01" required></div><div class="col-12 col-sm-6"><label class="form-label" for="productLife">Vida util (dias)</label><input class="form-control" name="dias_vida_util" id="productLife" type="number" min="0" max="3650" required></div></div>
            <div><label class="form-label" for="productDescription">Descripcion</label><textarea class="form-control" name="descripcion" id="productDescription" maxlength="2000" rows="4"></textarea></div>
        </div>
        <div class="modal-footer"><button class="btn btn-light" type="button" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-primary" type="submit">Guardar</button></div>
    </form></div></div>
</div>

<?php if ($canManageSupplies): ?>
<div class="modal fade catalog-supply-modal" id="newSupplyModal" tabindex="-1" aria-labelledby="newSupplyTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
        <form class="modal-content" id="newSupplyForm" novalidate>
            <div class="modal-header">
                <div><span class="catalog-supply-modal__eyebrow">Catálogo compartido</span><h5 class="modal-title" id="newSupplyTitle">Nuevo tipo de vaso</h5></div>
                <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3"><label class="form-label" for="newSupplyName">Nombre</label><input class="form-control" id="newSupplyName" name="nombre" maxlength="100" placeholder="Ej. Vaso para bebidas de 20 oz" required></div>
                <div class="row g-3">
                    <div class="col-7"><label class="form-label" for="newSupplyUsage">Uso</label><select class="form-select" id="newSupplyUsage" name="tipo_uso" required><option value="bebida">Bebidas</option><option value="batido">Batidos</option></select></div>
                    <div class="col-5"><label class="form-label" for="newSupplyMinimum">Stock mínimo</label><input class="form-control" id="newSupplyMinimum" name="stock_minimo" type="number" inputmode="numeric" min="0" max="100000" step="1" value="10" required></div>
                </div>
                <p class="small text-muted mt-3 mb-0">El tipo nuevo tendrá un registro de stock inicial en cada sucursal activa.</p>
            </div>
            <div class="modal-footer"><button class="btn btn-light" type="button" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-primary" type="submit"><i class="fa-solid fa-floppy-disk me-2" aria-hidden="true"></i>Crear tipo</button></div>
        </form>
    </div>
</div>

<div class="modal fade catalog-supply-modal" id="editSupplyModal" tabindex="-1" aria-labelledby="editSupplyTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
        <form class="modal-content" id="editSupplyForm" novalidate>
            <div class="modal-header">
                <div><span class="catalog-supply-modal__eyebrow">Catálogo compartido</span><h5 class="modal-title" id="editSupplyTitle">Editar tipo de vaso</h5></div>
                <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="id_insumo">
                <div class="mb-3"><label class="form-label" for="editSupplyName">Nombre</label><input class="form-control" id="editSupplyName" name="nombre" maxlength="100" required></div>
                <div class="row g-3">
                    <div class="col-7"><label class="form-label" for="editSupplyUsage">Uso</label><select class="form-select" id="editSupplyUsage" name="tipo_uso" required><option value="bebida">Bebidas</option><option value="batido">Batidos</option></select></div>
                    <div class="col-5"><label class="form-label" for="editSupplyMinimum">Stock mínimo</label><input class="form-control" id="editSupplyMinimum" name="stock_minimo" type="number" inputmode="numeric" min="0" max="100000" step="1" required></div>
                </div>
                <p class="small text-muted mt-3 mb-0">El nombre, tipo de uso y stock mínimo son compartidos por todas las sucursales.</p>
            </div>
            <div class="modal-footer"><button class="btn btn-light" type="button" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-primary" type="submit"><i class="fa-solid fa-floppy-disk me-2" aria-hidden="true"></i>Guardar cambios</button></div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php if ($canManageCategories): ?>
<div class="modal fade" id="categoryModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down"><div class="modal-content"><form id="categoryForm">
    <div class="modal-header"><h5 class="modal-title">Categoria</h5><button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
    <div class="modal-body"><input type="hidden" name="id_categoria" id="categoryId"><label class="form-label" for="categoryName">Nombre</label><input class="form-control" name="nombre_categoria" id="categoryName" maxlength="50" required></div>
    <div class="modal-footer"><button class="btn btn-light" type="button" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-primary" type="submit">Guardar</button></div>
</form></div></div></div>
<?php endif; ?>

<link rel="stylesheet" href="css/views/management.css?v=<?= filemtime(__DIR__ . '/../../public/css/views/management.css') ?>">
<link rel="stylesheet" href="css/views/catalogo.css?v=<?= filemtime(__DIR__ . '/../../public/css/views/catalogo.css') ?>">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.26.25"></script>
<script src="js/components/product-customization-editor.js?v=<?= filemtime(__DIR__ . '/../../public/js/components/product-customization-editor.js') ?>"></script>
<script src="js/views/catalogo.js?v=<?= filemtime(__DIR__ . '/../../public/js/views/catalogo.js') ?>"></script>
