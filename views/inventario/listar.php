<?php
?>

<div class="container-fluid app-page p-2 p-md-4">
    <header class="app-page-header">
        <div class="app-page-header__main">
            <span class="app-page-header__icon" aria-hidden="true"><i class="fa-solid fa-boxes-stacked"></i></span>
            <div class="app-page-header__copy">
                <h2 class="app-page-header__title">Inventario</h2>
                <p class="app-page-header__subtitle">Consulta, abastece y registra productos de forma rápida y ordenada.</p>
            </div>
        </div>
            <div class="app-page-header__actions">
                <?php if (\App\Security\Auth::hasPermission('products.manage')): ?>
                    <button class="btn btn-primary px-4 shadow-sm fw-bold text-white" data-bs-toggle="modal" data-bs-target="#modalNuevoProducto">
                        <i class="fa-solid fa-box-open me-2"></i>Registrar Producto
                    </button>
                <?php endif; ?>
            </div>
    </header>

    <?php if ($id_rol !== \App\Security\Auth::EMPLOYEE): ?>
    <div class="inventory-branch-filter mb-4">
        <label class="inventory-branch-filter__label" for="inventoryBranch">
            <i class="fa-solid fa-store" aria-hidden="true"></i>
            Sucursal
        </label>
        <div class="inventory-branch-filter__control">
            <select id="inventoryBranch" class="form-select" aria-describedby="inventoryBranchHelp" onchange="location.href='index.php?view=inventario&sucursal_id=' + encodeURIComponent(this.value)">
                <option value="">-- Selecciona una sucursal --</option>
                <?php foreach($sucursales as $s): ?>
                    <option value="<?= $s['id_sucursal'] ?>" <?= ($id_sucursal_filtro == $s['id_sucursal']) ? 'selected' : '' ?>>
                        <?= e($s['nombre_sucursal']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <span id="inventoryBranchHelp" class="visually-hidden">Filtra los productos por sucursal.</span>
    </div>
    <?php else: ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="alert alert-info border-0 rounded-4 shadow-sm mb-0">
                <i class="fa-solid fa-info-circle me-2"></i>
                <strong>Sucursal:</strong> Viendo inventario de <span class="fw-bold"><?= htmlspecialchars($nombre_sucursal_user) ?></span>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!$id_sucursal_filtro && $id_rol !== \App\Security\Auth::EMPLOYEE): ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="alert alert-warning border-0 rounded-4 shadow-sm mb-0">
                <i class="fa-solid fa-triangle-exclamation me-2"></i>
                Selecciona una sucursal para ver su inventario.
            </div>
        </div>
    </div>
    <?php endif; ?>


    <div class="card app-panel overflow-hidden">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 mb-3">
                <div>
                    <h5 class="fw-bold mb-1">Productos por sucursal</h5>
                    <p class="text-muted small mb-0">Vista resumida del stock actual y estado de cada producto.</p>
                </div>
                <div class="chip-pill">
                    <i class="fa-solid fa-layer-group"></i> Control de inventario
                </div>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 mobile-card-table inventory-main-table">
                <thead class="bg-light text-muted small text-uppercase">
                    <tr>
                        <th class="ps-4 py-3">Producto</th>
                        <th class="d-none d-lg-table-cell py-3">Categoría</th>
                        <th class="py-3">Stock</th>
                        <th class="py-3">Precio</th>
                        <th class="d-none d-md-table-cell py-3">Estado</th>
                        <th class="text-end pe-4 py-3">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($productos)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <div class="mb-3 display-5 opacity-50">
                                <i class="fa-solid fa-box-open"></i>
                            </div>
                            <h5 class="fw-bold mb-1">Sin productos</h5>
                            <p class="small mb-0">No hay productos registrados en esta sucursal.</p>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach($productos as $p): ?>

                    <tr>
                        <td class="ps-4 py-3" data-label="Producto">
                            <div class="fw-bold text-dark"><?= htmlspecialchars($p['nombre_producto']); ?></div>
                            <div class="d-flex flex-column d-lg-none mt-1">
                                <span class="badge bg-light text-secondary border rounded-pill px-2" style="width: fit-content; font-size: 0.7rem;">
                                    <i class="fa-solid fa-store me-1"></i> <?= htmlspecialchars($p['nombre_sucursal'] ?? 'N/A'); ?>
                                </span>
                            </div>
                        </td>
                        <td class="d-none d-lg-table-cell text-muted small fw-medium py-3" data-label="Categoría">
                            <span class="bg-light px-2 py-1 rounded-3 border">
                                <?= htmlspecialchars($p['nombre_categoria']); ?>
                            </span>
                        </td>
                        <td class="py-3" data-label="Stock">
                            <div class="fw-bold fs-6"><?= $p['stock_actual']; ?> <small class="text-muted fw-normal">unid.</small></div>
                            <div class="d-md-none mt-1">
                                <?php if($p['stock_actual'] <= 0): ?>
                                    <span class="badge bg-danger rounded-pill px-2 py-1" style="font-size: 0.65rem;">AGOTADO</span>
                                <?php elseif($p['stock_actual'] <= $p['stock_minimo']): ?>
                                    <span class="badge bg-warning text-dark rounded-pill px-2 py-1" style="font-size: 0.65rem;">BAJO</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="small fw-bold text-success py-3" data-label="Precio">L. <?= number_format($p['precio_base'], 2); ?></td>
                        <td class="d-none d-md-table-cell py-3" data-label="Estado">
                            <?php if($p['stock_actual'] <= 0): ?>
                                <span class="badge bg-danger bg-opacity-10 text-danger rounded-pill px-3 py-2 fw-bold">AGOTADO</span>
                            <?php elseif($p['stock_actual'] <= $p['stock_minimo']): ?>
                                <span class="badge bg-warning bg-opacity-10 text-warning rounded-pill px-3 py-2 fw-bold text-dark">STOCK BAJO</span>
                            <?php else: ?>
                                <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3 py-2 fw-bold">EN STOCK</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end pe-4 py-3" data-label="Acciones">
                            <div class="d-flex justify-content-end gap-2">
                                <?php if (\App\Security\Auth::canAccessBranch((int) $p['id_sucursal'], 'inventory.adjust')): ?>
                                    <button class="btn btn-sm btn-outline-primary rounded-circle shadow-sm d-flex align-items-center justify-content-center hover-lift" style="width: 35px; height: 35px;" data-bs-toggle="modal" data-bs-target="#modalAbastecer<?= $p['id_producto'] ?>_<?= $p['id_sucursal'] ?>" title="Abastecer">
                                        <i class="fa-solid fa-plus"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger rounded-circle shadow-sm d-flex align-items-center justify-content-center hover-lift" style="width: 35px; height: 35px;" data-bs-toggle="modal" data-bs-target="#modalMerma<?= $p['id_producto'] ?>_<?= $p['id_sucursal'] ?>" title="Merma">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                <?php else: ?>
                                    <span class="text-muted small"><i class="fa-solid fa-ban"></i></span>
                                <?php endif; ?>
                            </div>
                        </td>

                        <div class="modal fade" id="modalAbastecer<?= $p['id_producto'] ?>_<?= $p['id_sucursal'] ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-sm modal-dialog-centered modal-fullscreen-sm-down">
                                <div class="modal-content border-0 shadow-lg rounded-4">
                                    <div class="modal-header border-0 bg-primary bg-opacity-10 rounded-top-4">
                                        <h6 class="fw-bold text-primary m-0"><i class="fa-solid fa-plus-circle me-2"></i>Abastecer Producto</h6>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <form class="formAbastecer">
                                        <div class="modal-body text-center px-4">
                                            <p class="fw-bold mb-3"><?= htmlspecialchars($p['nombre_producto']) ?></p>
                                            
                                            <input type="hidden" name="id_producto" value="<?= $p['id_producto'] ?>">
                                            <input type="hidden" name="id_sucursal" value="<?= $p['id_sucursal'] ?>">
                                            
                                            <div class="form-floating mb-3">
                                                <input type="number" name="cantidad" id="cant_<?= $p['id_producto'] ?>_<?= $p['id_sucursal'] ?>" class="form-control form-control-lg text-center border-0 bg-light rounded-3 fw-bold fs-4" value="10" min="1" required>
                                                <label for="cant_<?= $p['id_producto'] ?>_<?= $p['id_sucursal'] ?>">Cantidad a sumar</label>
                                            </div>
                                        </div>
                                        <div class="modal-footer border-0 px-4 pb-4 pt-0">
                                            <button type="submit" class="btn btn-primary w-100 rounded-pill py-2 fw-bold text-white shadow-sm">Confirmar Ingreso</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div class="modal fade" id="modalMerma<?= $p['id_producto'] ?>_<?= $p['id_sucursal'] ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-sm modal-dialog-centered modal-fullscreen-sm-down">
                                <div class="modal-content border-0 shadow-lg rounded-4">
                                    <div class="modal-header bg-danger text-white border-0 rounded-top-4">
                                        <h6 class="fw-bold m-0"><i class="fa-solid fa-arrow-trend-down me-2"></i>Registrar Merma</h6>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                    </div>
                                    <form class="formRegistroMerma">
                                        <div class="modal-body px-4">
                                            <p class="text-center fw-bold mb-3"><?= htmlspecialchars($p['nombre_producto']) ?></p>
                                            
                                            <input type="hidden" name="id_producto_merma" value="<?= $p['id_producto'] ?>">
                                            <input type="hidden" name="id_sucursal_merma" value="<?= $p['id_sucursal'] ?>">
                                            
                                            <div class="mb-3">
                                                <label class="small fw-bold text-muted mb-1">Cantidad a descontar:</label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-light border-0"><i class="fa-solid fa-minus text-danger"></i></span>
                                                    <input type="number" name="cantidad_merma" class="form-control text-center bg-light border-0 fw-bold" max="<?= $p['stock_actual'] ?>" min="1" required>
                                                </div>
                                                <small class="text-muted" style="font-size: 0.7rem;">Stock actual: <?= $p['stock_actual'] ?></small>
                                            </div>
                                            <div class="mb-2">
                                                <label class="small fw-bold text-muted mb-1">Motivo:</label>
                                                <select name="motivo_merma" class="form-select bg-light border-0" required>
                                                    <option value="">-- Seleccionar --</option>
                                                    <option value="Caducidad">⏳ Caducidad</option>
                                                    <option value="Daño/Rotura">💔 Daño o Rotura</option>
                                                    <option value="Extravío">❓ Extravío</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="modal-footer border-0 px-4 pb-4 pt-0">
                                            <button type="submit" class="btn btn-danger w-100 rounded-pill py-2 fw-bold shadow-sm">Descontar Stock</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalNuevoProducto" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom-0 pt-4 px-4 pb-0">
                <div>
                    <h5 class="fw-bold m-0"><i class="fa-solid fa-box-open text-primary me-2"></i>Registrar Producto</h5>
                    <p class="text-muted small mb-0">Elige el tipo de producto y completa los campos necesarios.</p>
                </div>
                <button type="button" class="btn-close bg-light rounded-circle p-2" data-bs-dismiss="modal"></button>
            </div>
            <form id="formNuevoProducto" enctype="multipart/form-data">
                <div class="modal-body px-3 px-md-4 py-3">
                    <div class="form-section-card mb-3">
                        <label class="small text-muted fw-bold mb-2 ms-1">Tipo de producto</label>
                        <div class="row g-2">
                            <div class="col-12 col-sm-6">
                                <div class="form-check border rounded-4 p-3 bg-white">
                                    <input class="form-check-input" type="radio" name="tipo_producto" id="tipoPastel" value="pastel" checked>
                                    <label class="form-check-label ms-2 fw-bold" for="tipoPastel"><i class="fa-solid fa-cake-candles me-2 text-primary"></i>Pastel</label>
                                </div>
                            </div>
                            <div class="col-12 col-sm-6">
                                <div class="form-check border rounded-4 p-3 bg-white">
                                    <input class="form-check-input" type="radio" name="tipo_producto" id="tipoPanaderia" value="panaderia">
                                    <label class="form-check-label ms-2 fw-bold" for="tipoPanaderia"><i class="fa-solid fa-bread-slice me-2 text-primary"></i>Panadería / Otros</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-section-card mb-3">
                        <div class="form-floating mb-3">
                            <input type="text" name="nombre_producto" id="nombreProducto" class="form-control border-0 bg-light rounded-3" placeholder="Nombre del producto" required>
                            <label for="nombreProducto" class="text-muted">Nombre del producto</label>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-12 col-sm-6">
                                <label class="small text-muted fw-bold mb-1 ms-1">Categoría existente</label>
                                <select name="id_categoria" class="form-select border-0 bg-light rounded-3 py-2">
                                    <option value="">-- Seleccionar --</option>
                                    <?php foreach($categorias as $cat): ?>
                                        <option value="<?= (int) $cat['id_categoria'] ?>"><?= e($cat['nombre_categoria']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12 col-sm-6">
                                <label class="small text-muted fw-bold mb-1 ms-1">Nueva categoría</label>
                                <input type="text" name="nueva_categoria_nombre" class="form-control border-0 bg-light rounded-3 py-2" placeholder="Escribir nombre...">
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-12 col-sm-6">
                                <label class="small text-muted fw-bold mb-1 ms-1">Precio (L.)</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-0 rounded-start-3 text-muted">L.</span>
                                    <input type="number" step="0.01" name="precio_base" class="form-control border-0 bg-light rounded-end-3" required>
                                </div>
                            </div>
                            <div class="col-12 col-sm-6">
                                <label class="small text-muted fw-bold mb-1 ms-1">Stock inicial</label>
                                <input type="number" name="stock_inicial" class="form-control border-0 bg-light rounded-3" value="0">
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-12 col-sm-6">
                                <label class="small text-muted fw-bold mb-1 ms-1">Vida útil</label>
                                <div class="input-group">
                                    <input type="number" min="0" class="form-control border-0 bg-light rounded-start-3" name="dias_vida_util" placeholder="0">
                                    <span class="input-group-text border-0 bg-light rounded-end-3 text-muted">días</span>
                                </div>
                            </div>
                            <div class="col-12 col-sm-6">
                                <label class="small text-muted fw-bold mb-1 ms-1">Imagen (opcional)</label>
                                <input type="file" name="imagen" class="form-control border-0 bg-light rounded-3" accept="image/*">
                            </div>
                        </div>
                    </div>

                    <div class="form-section-card mb-3" id="camposPastel" style="display:block;">
                        <h6 class="fw-bold mb-3"><i class="fa-solid fa-cake-candles me-2 text-primary"></i>Detalles para pasteles</h6>
                        <div class="row g-3">
                            <div class="col-12 col-sm-6">
                                <label class="small text-muted fw-bold mb-1 ms-1">Tamaño</label>
                                <input type="text" name="tamano" class="form-control border-0 bg-light rounded-3" placeholder="Ej. 8 pulgadas">
                            </div>
                            <div class="col-12 col-sm-6">
                                <label class="small text-muted fw-bold mb-1 ms-1">Cantidad de tortas</label>
                                <input type="number" name="cantidad_tortas" class="form-control border-0 bg-light rounded-3" value="1" min="1">
                            </div>
                        </div>
                        <div class="d-flex align-items-center justify-content-between gap-2 mt-4 mb-3">
                            <h6 class="fw-bold mb-0"><i class="fa-solid fa-sliders text-primary me-2"></i>Opciones y recargos</h6>
                            <button class="btn btn-sm btn-outline-primary" id="addNewProductCustomizationGroup" type="button"><i class="fa-solid fa-plus me-1"></i>Grupo</button>
                        </div>
                        <div id="newProductCustomizationGroups" class="customization-groups"></div>
                    </div>

                    <div class="form-section-card mb-3" id="camposPanaderia" style="display:none;">
                        <h6 class="fw-bold mb-3"><i class="fa-solid fa-bread-slice me-2 text-primary"></i>Detalles de panadería / otros</h6>
                        <div class="form-floating">
                            <textarea name="observaciones" class="form-control border-0 bg-light rounded-4" placeholder="Observaciones" style="min-height:100px"></textarea>
                            <label class="text-muted">Observaciones adicionales</label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="small text-muted fw-bold mb-2 ms-1">Sucursales disponibles</label>
                        <div class="p-3 border-0 bg-light rounded-4 shadow-sm" style="max-height: 170px; overflow-y: auto;">
                            <?php foreach($sucursales_modal as $s): ?>
                                <div class="form-check custom-checkbox mb-2">
                                    <input class="form-check-input" type="checkbox" name="id_sucursal[]" value="<?= $s['id_sucursal'] ?>" id="s<?= $s['id_sucursal'] ?>">
                                    <label class="form-check-label ms-1" for="s<?= $s['id_sucursal'] ?>">
                                        <?= e($s['nombre_sucursal']) ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 px-4 pb-4 pt-2">
                    <button type="submit" class="btn btn-primary py-3 fw-bold text-white shadow-sm fs-6">
                        <i class="fa-solid fa-floppy-disk me-2"></i>Guardar producto
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<link rel="stylesheet" href="css/views/inventario.css?v=<?= filemtime(__DIR__ . '/../../public/css/views/inventario.css') ?>">

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.26.25"></script>
<script src="js/components/product-customization-editor.js?v=<?= filemtime(__DIR__ . '/../../public/js/components/product-customization-editor.js') ?>"></script>
<script src="js/views/inventario.js?v=<?= filemtime(__DIR__ . '/../../public/js/views/inventario.js') ?>"></script>
