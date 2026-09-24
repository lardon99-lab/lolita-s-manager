<?php
?>

<div class="container-fluid app-page p-2 p-md-4">
    <header class="app-page-header">
        <div class="app-page-header__main">
            <span class="app-page-header__icon" aria-hidden="true"><i class="fa-solid fa-boxes-stacked"></i></span>
            <div class="app-page-header__copy">
                <h2 class="app-page-header__title">Inventario</h2>
                <p class="app-page-header__subtitle">
                    <?= \App\Security\Auth::hasPermission('inventory.adjust')
                        ? 'Consulta, abastece y registra productos de forma rápida y ordenada.'
                        : 'Consulta existencias y registra las mermas de tu sucursal.' ?>
                </p>
            </div>
        </div>
            <div class="app-page-header__actions">
                <?php if ($id_sucursal_filtro && \App\Security\Auth::canAccessBranch((int) $id_sucursal_filtro, 'inventory.adjust')): ?>
                    <button type="button" class="btn btn-success px-4 shadow-sm fw-bold" data-restock-open data-restock-branch="<?= (int) $id_sucursal_filtro ?>">
                        <i class="fa-solid fa-boxes-packing me-2" aria-hidden="true"></i>Abastecer inventario
                    </button>
                    <?php if ($insumos !== []): ?>
                    <button type="button" class="btn btn-outline-success px-4 fw-bold" data-bs-toggle="modal" data-bs-target="#modalAbastecerInsumos">
                        <i class="fa-solid fa-glass-water me-2" aria-hidden="true"></i>Abastecer vasos
                    </button>
                    <?php endif; ?>
                <?php endif; ?>
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
                                    <button type="button"
                                            class="btn btn-sm btn-outline-primary rounded-circle shadow-sm d-flex align-items-center justify-content-center hover-lift btn-abastecer-directo"
                                            style="width: 35px; height: 35px;"
                                            data-producto="<?= (int) $p['id_producto'] ?>"
                                            data-sucursal="<?= (int) $p['id_sucursal'] ?>"
                                            title="Abastecer <?= e($p['nombre_producto']) ?>"
                                            aria-label="Abastecer <?= e($p['nombre_producto']) ?>">
                                        <i class="fa-solid fa-plus"></i>
                                    </button>
                                <?php endif; ?>
                                <?php if (\App\Security\Auth::canAccessBranch((int) $p['id_sucursal'], 'inventory.waste')): ?>
                                    <button class="btn btn-sm btn-outline-danger rounded-circle shadow-sm d-flex align-items-center justify-content-center hover-lift" style="width: 35px; height: 35px;" data-bs-toggle="modal" data-bs-target="#modalMerma<?= $p['id_producto'] ?>_<?= $p['id_sucursal'] ?>" title="Merma">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                <?php endif; ?>
                                <?php if (!\App\Security\Auth::canAccessBranch((int) $p['id_sucursal'], 'inventory.adjust') && !\App\Security\Auth::canAccessBranch((int) $p['id_sucursal'], 'inventory.waste')): ?>
                                    <span class="text-muted small"><i class="fa-solid fa-ban"></i></span>
                                <?php endif; ?>
                            </div>
                        </td>

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

    <?php if ($id_sucursal_filtro && $insumos !== []): ?>
    <section class="supply-inventory mt-4" aria-labelledby="supplyInventoryTitle">
        <header class="supply-inventory__header">
            <div>
                <h5 id="supplyInventoryTitle" class="fw-bold mb-1">Vasos e insumos compartidos</h5>
                <p class="text-muted small mb-0">Una sola existencia alimenta todas las bebidas que utilizan el mismo vaso.</p>
            </div>
            <span class="chip-pill"><i class="fa-solid fa-link"></i> Stock compartido</span>
        </header>
        <div class="supply-grid">
            <?php foreach ($insumos as $insumo):
                $supplyStock = (float) $insumo['stock_actual'];
                $supplyMinimum = (float) $insumo['stock_minimo'];
                $supplyStatus = $supplyStock <= 0 ? 'danger' : ($supplyStock <= $supplyMinimum ? 'warning' : 'success');
            ?>
            <article class="supply-card supply-card--<?= $supplyStatus ?>">
                <span class="supply-card__icon"><i class="fa-solid fa-glass-water" aria-hidden="true"></i></span>
                <div class="supply-card__content">
                    <h6><?= e($insumo['nombre']) ?></h6>
                    <p><?= rtrim(rtrim(number_format($supplyStock, 3, '.', ''), '0'), '.') ?> <?= e($insumo['unidad_medida']) ?>(s)</p>
                </div>
                <span class="supply-card__status"><?= $supplyStock <= 0 ? 'Agotado' : ($supplyStock <= $supplyMinimum ? 'Stock bajo' : 'Disponible') ?></span>
            </article>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>
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
                        <div class="row g-2 product-type-grid">
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
                            <div class="col-12 col-sm-6">
                                <div class="form-check border rounded-4 p-3 bg-white">
                                    <input class="form-check-input" type="radio" name="tipo_producto" id="tipoBebida" value="bebida">
                                    <label class="form-check-label ms-2 fw-bold" for="tipoBebida"><i class="fa-solid fa-mug-hot me-2 text-primary"></i>Bebida</label>
                                </div>
                            </div>
                            <div class="col-12 col-sm-6">
                                <div class="form-check border rounded-4 p-3 bg-white">
                                    <input class="form-check-input" type="radio" name="tipo_producto" id="tipoBatido" value="batido">
                                    <label class="form-check-label ms-2 fw-bold" for="tipoBatido"><i class="fa-solid fa-blender me-2 text-primary"></i>Batido / licuado</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-section-card mb-3">
                        <div class="form-floating mb-3">
                            <input type="text" name="nombre_producto" id="nombreProducto" class="form-control border-0 bg-light rounded-3" placeholder="Nombre del producto" minlength="2" maxlength="100" required>
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
                                    <input type="number" step="0.01" min="0.01" max="1000000" name="precio_base" class="form-control border-0 bg-light rounded-end-3" required>
                                </div>
                            </div>
                            <div class="col-12 col-sm-6">
                                <label class="small text-muted fw-bold mb-1 ms-1">Stock inicial</label>
                                <input type="number" name="stock_inicial" class="form-control border-0 bg-light rounded-3" value="0" min="0" max="100000" step="1" data-product-stock-input>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-12 col-sm-6">
                                <label class="small text-muted fw-bold mb-1 ms-1">Vida útil</label>
                                <div class="input-group">
                                    <input type="number" min="0" max="3650" step="1" class="form-control border-0 bg-light rounded-start-3" name="dias_vida_util" placeholder="0" data-product-stock-input>
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
                                <input type="number" name="cantidad_tortas" class="form-control border-0 bg-light rounded-3" value="1" min="1" max="100" step="1">
                            </div>
                        </div>
                        <div class="mt-4 mb-3">
                            <h6 class="fw-bold mb-1"><i class="fa-solid fa-sliders text-primary me-2"></i>Opciones y recargos</h6>
                            <p class="text-muted small mb-0">Agrega los sabores, rellenos y cubiertas disponibles para este pastel.</p>
                        </div>
                        <div id="newProductCustomizationGroups" class="cake-option-groups"></div>

                        <section class="cake-design-policy mt-3">
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" role="switch" name="permite_diseno" value="1" id="allowCakeDesign">
                                <label class="form-check-label fw-bold" for="allowCakeDesign">Permitir diseno personalizado</label>
                                <div class="small text-muted">Instrucciones especiales y referencia visual al crear el pedido. Los colores y la frase siempre están disponibles para pasteles.</div>
                            </div>
                            <div id="cakeDesignPolicyFields" class="row g-3 d-none">
                                <div class="col-12 col-sm-6">
                                    <label class="small text-muted fw-bold mb-1" for="cakeDesignSurcharge">Recargo por diseno</label>
                                    <div class="input-group"><span class="input-group-text">L.</span><input id="cakeDesignSurcharge" name="recargo_diseno" class="form-control" type="number" min="0" max="1000000" step="0.01" value="0.00"></div>
                                </div>
                                <div class="col-12 col-sm-6 d-flex align-items-end">
                                    <div class="form-check form-switch cake-image-switch">
                                        <input class="form-check-input" type="checkbox" role="switch" name="permite_imagen" value="1" id="allowCakeDesignImage" checked>
                                        <label class="form-check-label" for="allowCakeDesignImage">Permitir imagen de referencia</label>
                                    </div>
                                </div>
                            </div>
                        </section>
                    </div>

                    <div class="form-section-card mb-3" id="camposPanaderia" style="display:none;">
                        <h6 class="fw-bold mb-3"><i class="fa-solid fa-bread-slice me-2 text-primary"></i>Detalles de panadería / otros</h6>
                        <div class="form-floating">
                            <textarea name="observaciones" class="form-control border-0 bg-light rounded-4" placeholder="Observaciones" style="min-height:100px"></textarea>
                            <label class="text-muted">Observaciones adicionales</label>
                        </div>
                    </div>

                    <div class="form-section-card mb-3" id="camposBebida" hidden>
                        <h6 class="fw-bold mb-1"><i class="fa-solid fa-mug-hot me-2 text-primary"></i>Control de bebida</h6>
                        <p class="text-muted small">Todas las bebidas del mismo tamaño descontarán del mismo inventario de vasos.</p>
                        <label class="small text-muted fw-bold mb-1" for="beverageSupply">Tamaño de vaso</label>
                        <select id="beverageSupply" name="id_insumo" class="form-select" required disabled>
                            <option value="">Seleccionar vaso...</option>
                            <?php foreach ($insumos_catalogo as $supply): ?>
                                <?php if (in_array($supply['codigo'], ['cup_8oz', 'cup_12oz'], true)): ?>
                                <option value="<?= (int) $supply['id_insumo'] ?>"><?= e($supply['nombre']) ?></option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-section-card mb-3" id="camposBatido" hidden>
                        <h6 class="fw-bold mb-1"><i class="fa-solid fa-blender me-2 text-primary"></i>Opciones del batido</h6>
                        <p class="text-muted small">La primera fruta está incluida. La fruta adicional y la leche deslactosada aplican el recargo indicado.</p>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="small text-muted fw-bold mb-1" for="shakeSupply">Vaso estándar</label>
                                <select id="shakeSupply" name="id_insumo" class="form-select" required disabled>
                                    <?php foreach ($insumos_catalogo as $supply): ?>
                                        <?php if ($supply['codigo'] === 'cup_shake'): ?>
                                        <option value="<?= (int) $supply['id_insumo'] ?>" selected><?= e($supply['nombre']) ?></option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="small text-muted fw-bold mb-1" for="shakeFruits">Frutas disponibles</label>
                                <textarea id="shakeFruits" name="frutas" class="form-control" rows="4" placeholder="Fresa&#10;Banano&#10;Mango" required disabled></textarea>
                                <small class="text-muted">Escribe una fruta por línea.</small>
                            </div>
                            <div class="col-12 col-sm-4">
                                <label class="small text-muted fw-bold mb-1" for="shakeMaxFruits">Máximo de frutas</label>
                                <input id="shakeMaxFruits" name="maximo_frutas" type="number" class="form-control" min="1" max="10" value="3" required disabled>
                            </div>
                            <div class="col-12 col-sm-4">
                                <label class="small text-muted fw-bold mb-1" for="shakeFruitSurcharge">Fruta adicional</label>
                                <div class="input-group"><span class="input-group-text">L.</span><input id="shakeFruitSurcharge" name="recargo_fruta_extra" type="number" class="form-control" min="0" step="0.01" value="15.00" required disabled></div>
                            </div>
                            <div class="col-12 col-sm-4">
                                <label class="small text-muted fw-bold mb-1" for="shakeMilkSurcharge">Leche deslactosada</label>
                                <div class="input-group"><span class="input-group-text">L.</span><input id="shakeMilkSurcharge" name="recargo_deslactosada" type="number" class="form-control" min="0" step="0.01" value="15.00" required disabled></div>
                            </div>
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

<?php if ($id_sucursal_filtro && $insumos !== [] && \App\Security\Auth::canAccessBranch((int) $id_sucursal_filtro, 'inventory.adjust')): ?>
<div class="modal fade supply-restock-modal" id="modalAbastecerInsumos" tabindex="-1" aria-labelledby="supplyRestockTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down">
        <form class="modal-content" id="formAbastecerInsumos">
            <header class="modal-header">
                <div><span class="supply-restock-modal__eyebrow">Entrada de inventario</span><h5 class="modal-title" id="supplyRestockTitle">Abastecer vasos</h5></div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </header>
            <div class="modal-body">
                <input type="hidden" name="id_sucursal" value="<?= (int) $id_sucursal_filtro ?>">
                <input type="hidden" name="idempotency_key" value="">
                <div class="supply-restock-list">
                    <?php foreach ($insumos as $insumo): ?>
                    <label class="supply-restock-row">
                        <span><strong><?= e($insumo['nombre']) ?></strong><small>Actual: <?= (float) $insumo['stock_actual'] ?></small></span>
                        <input type="number" class="form-control" min="0" max="100000" step="1" value="0" data-supply-id="<?= (int) $insumo['id_insumo'] ?>" aria-label="Cantidad de <?= e($insumo['nombre']) ?>">
                    </label>
                    <?php endforeach; ?>
                </div>
                <label class="small fw-bold text-muted mt-3" for="supplyRestockNotes">Observaciones</label>
                <textarea id="supplyRestockNotes" name="observaciones" class="form-control" rows="2" maxlength="500"></textarea>
            </div>
            <footer class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-success"><i class="fa-solid fa-floppy-disk me-2"></i>Registrar entrada</button>
            </footer>
        </form>
    </div>
</div>
<?php endif; ?>

<?php
$restockProducts = $productos;
$restockDefaultBranch = (int) ($id_sucursal_filtro ?? 0);
if (\App\Security\Auth::hasPermission('inventory.adjust')) {
    include __DIR__ . '/partials/restock-panel.php';
}
?>

<link rel="stylesheet" href="css/views/inventario.css?v=<?= filemtime(__DIR__ . '/../../public/css/views/inventario.css') ?>">

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.26.25"></script>
<script src="js/components/cake-configuration-editor.js?v=<?= filemtime(__DIR__ . '/../../public/js/components/cake-configuration-editor.js') ?>"></script>
<script src="js/views/inventario.js?v=<?= filemtime(__DIR__ . '/../../public/js/views/inventario.js') ?>"></script>
<script src="js/components/supply-restock.js?v=<?= filemtime(__DIR__ . '/../../public/js/components/supply-restock.js') ?>"></script>
