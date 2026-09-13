<?php $canManageCategories = \App\Security\Auth::hasPermission('categories.manage'); ?>

<div class="container-fluid p-2 p-md-4 management-page catalog-page">
    <header class="catalog-header d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 mb-4">
        <div class="min-w-0">
            <h2 class="fw-bold mb-1"><i class="fa-solid fa-tags text-primary me-2"></i>Catalogo</h2>
            <p class="text-muted mb-0">Productos y categorias disponibles en el sistema.</p>
        </div>
        <a class="btn btn-primary catalog-header__action" href="index.php?view=inventario"><i class="fa-solid fa-plus me-2"></i>Nuevo producto</a>
    </header>

    <ul class="nav nav-tabs catalog-tabs mb-3" role="tablist">
        <li class="nav-item" role="presentation"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#productsTab" type="button" role="tab">Productos</button></li>
        <?php if ($canManageCategories): ?>
            <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#categoriesTab" type="button" role="tab">Categorias</button></li>
        <?php endif; ?>
    </ul>

    <div class="tab-content">
        <section class="tab-pane fade show active" id="productsTab" role="tabpanel">
            <div class="table-responsive bg-white border rounded-3 d-none d-md-block">
                <table class="table table-hover align-middle mb-0 management-table">
                    <thead><tr><th>Producto</th><th>Categoria</th><th>Precio</th><th>Sucursales</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead>
                    <tbody>
                    <?php foreach ($productos as $producto): ?>
                        <?php $productJson = e(json_encode($producto, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP)); ?>
                        <tr>
                            <td class="fw-semibold"><?= e($producto['nombre_producto']) ?></td>
                            <td><?= e($producto['nombre_categoria']) ?></td>
                            <td class="text-nowrap">L. <?= number_format((float) $producto['precio_base'], 2) ?></td>
                            <td><?= e($producto['sucursales'] ?: 'Sin inventario') ?></td>
                            <td><span class="badge <?= $producto['estado'] === 'Activo' ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= e($producto['estado']) ?></span></td>
                            <td class="text-end text-nowrap">
                                <button class="btn btn-sm btn-outline-primary js-edit-product" type="button" title="Editar" data-product='<?= $productJson ?>'><i class="fa-solid fa-pen"></i></button>
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
                    <article class="catalog-mobile-item">
                        <div class="catalog-mobile-item__header">
                            <h3><?= e($producto['nombre_producto']) ?></h3>
                            <span class="badge <?= $producto['estado'] === 'Activo' ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= e($producto['estado']) ?></span>
                        </div>
                        <dl class="catalog-mobile-item__details">
                            <div><dt>Categoria</dt><dd><?= e($producto['nombre_categoria']) ?></dd></div>
                            <div><dt>Precio</dt><dd>L. <?= number_format((float) $producto['precio_base'], 2) ?></dd></div>
                            <div><dt>Sucursales</dt><dd><?= e($producto['sucursales'] ?: 'Sin inventario') ?></dd></div>
                        </dl>
                        <div class="catalog-mobile-item__actions">
                            <button class="btn btn-outline-primary js-edit-product" type="button" data-product='<?= $productJson ?>'><i class="fa-solid fa-pen me-2"></i>Editar</button>
                            <button class="btn btn-outline-secondary js-state-product" type="button" data-id="<?= (int) $producto['id_producto'] ?>" data-state="<?= $producto['estado'] === 'Activo' ? 'Inactivo' : 'Activo' ?>"><i class="fa-solid fa-power-off me-2"></i><?= $producto['estado'] === 'Activo' ? 'Desactivar' : 'Activar' ?></button>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <?php if ($canManageCategories): ?>
        <section class="tab-pane fade" id="categoriesTab" role="tabpanel">
            <div class="d-flex justify-content-end mb-3"><button class="btn btn-primary catalog-category-add" data-bs-toggle="modal" data-bs-target="#categoryModal"><i class="fa-solid fa-plus me-2"></i>Nueva categoria</button></div>
            <div class="table-responsive bg-white border rounded-3 d-none d-md-block">
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
        </section>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="productModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down"><div class="modal-content"><form id="productForm">
        <div class="modal-header"><h5 class="modal-title">Editar producto</h5><button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
        <div class="modal-body">
            <input type="hidden" name="id_producto" id="productId">
            <div class="mb-3"><label class="form-label" for="productName">Nombre</label><input class="form-control" name="nombre_producto" id="productName" maxlength="150" required></div>
            <div class="mb-3"><label class="form-label" for="productCategory">Categoria</label><select class="form-select" name="id_categoria" id="productCategory" required><?php foreach ($categorias as $categoria): if ($categoria['estado'] !== 'Activo') continue; ?><option value="<?= (int) $categoria['id_categoria'] ?>"><?= e($categoria['nombre_categoria']) ?></option><?php endforeach; ?></select></div>
            <div class="row g-3 mb-3"><div class="col-12 col-sm-6"><label class="form-label" for="productPrice">Precio</label><input class="form-control" name="precio_base" id="productPrice" type="number" min="0" max="1000000" step="0.01" required></div><div class="col-12 col-sm-6"><label class="form-label" for="productLife">Vida util (dias)</label><input class="form-control" name="dias_vida_util" id="productLife" type="number" min="0" max="3650" required></div></div>
            <div><label class="form-label" for="productDescription">Descripcion</label><textarea class="form-control" name="descripcion" id="productDescription" maxlength="2000" rows="4"></textarea></div>
        </div>
        <div class="modal-footer"><button class="btn btn-light" type="button" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-primary" type="submit">Guardar</button></div>
    </form></div></div>
</div>

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
<script src="js/views/catalogo.js"></script>
