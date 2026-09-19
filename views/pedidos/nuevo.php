<div class="container-fluid app-page p-2 p-md-4">
    <header class="app-page-header">
        <div class="app-page-header__main">
            <span class="app-page-header__icon" aria-hidden="true"><i class="fa-solid fa-cake-candles"></i></span>
            <div class="app-page-header__copy">
                <h2 class="app-page-header__title">Nuevo pedido especial</h2>
                <p class="app-page-header__subtitle">Registra los detalles, fecha de entrega y personalización del cliente.</p>
            </div>
        </div>
    </header>

    <form action="api.php?resource=pedidos&action=crear" method="POST" id="formNuevoPedido">
        <div class="row g-3 g-md-4">
            
            <div class="col-12 col-lg-4">
                <div class="card app-panel p-3 p-md-4 h-100">
                    <div class="d-flex align-items-center mb-4">
                        <div class="bg-primary bg-opacity-10 p-2 rounded-circle me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                            <i class="fa-solid fa-address-card text-primary fs-5"></i>
                        </div>
                        <h5 class="fw-bold mb-0">Información General</h5>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Cliente</label>
                        <div class="input-group shadow-sm rounded-3 overflow-hidden">
                            <span class="input-group-text border-0 bg-light text-muted px-3"><i class="fa-solid fa-magnifying-glass"></i></span>
                            <input list="lista-clientes" name="id_cliente_search" id="cliente-input" class="form-control border-0 bg-light py-2" placeholder="Buscar cliente..." required>
                            <datalist id="lista-clientes">
                                <?php foreach($clientes as $c): ?>
                                    <option data-id="<?= $c['id_cliente'] ?>" value="<?= htmlspecialchars($c['nombre_completo']) ?>">
                                <?php endforeach; ?>
                            </datalist>
                            <button type="button" class="btn btn-primary px-3 transition-hover" data-bs-toggle="modal" data-bs-target="#modalCliente" title="Nuevo Cliente">
                                <i class="fa-solid fa-user-plus"></i>
                            </button>
                        </div>
                        <input type="hidden" name="id_cliente" id="id_cliente_real">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-danger"><i class="fa-regular fa-clock me-1"></i> Fecha y Hora de Entrega</label>
                        <input type="datetime-local" name="fecha_entrega" class="form-control border-0 bg-light shadow-sm rounded-3 py-2 fw-medium" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted" for="order-branch">Sucursal de Entrega</label>
                        <div class="order-select-field">
                            <i class="fa-solid fa-store" aria-hidden="true"></i>
                            <select name="id_sucursal" id="order-branch" class="form-select border-0 bg-light py-2 fw-medium" required>
                                <option value="">-- Seleccionar --</option>
                                <?php foreach($sucursales as $s): ?>
                                    <option value="<?= $s['id_sucursal'] ?>" <?= (isset($_SESSION['id_sucursal']) && $_SESSION['id_sucursal'] == $s['id_sucursal']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($s['nombre_sucursal']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-0">
                        <label class="form-label small fw-bold text-muted">Observaciones del Pedido</label>
                        <textarea name="observaciones" class="form-control border-0 bg-light shadow-sm rounded-3 p-3" rows="3" placeholder="Ej: No muy dulce, empacar por separado, etc..."></textarea>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-8">
                <div class="card app-panel p-3 p-md-4 h-100 d-flex flex-column">
                    <div class="d-flex align-items-center mb-4">
                        <div class="bg-success bg-opacity-10 p-2 rounded-circle me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                            <i class="fa-solid fa-list-check text-success fs-5"></i>
                        </div>
                        <h5 class="fw-bold mb-0">Detalle del Pedido</h5>
                    </div>
                    
                    <div class="row g-2 mb-4 bg-light p-3 rounded-4 border shadow-sm align-items-end">
                        <div class="col-12 col-md-7">
                            <label class="form-label small fw-bold text-muted mb-1" for="select-producto">Producto</label>
                            <select id="select-producto" class="form-select border-0 shadow-sm py-2">
                                <option value="">Selecciona un producto...</option>
                                <?php foreach($productos as $p): ?>
                                    <option value="<?= $p['id_producto'] ?>" 
                                            data-precio="<?= $p['precio_base'] ?>" 
                                            data-branches="<?= e($p['branch_ids'] ?? '') ?>"
                                            data-nombre="<?= e($p['nombre_producto'] ?? '') ?>">
                                        <?= htmlspecialchars($p['nombre_producto']) ?> (L. <?= number_format($p['precio_base'], 2) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-5 col-md-2">
                            <label class="form-label small fw-bold text-muted mb-1" for="cant-producto">Cant.</label>
                            <input type="number" id="cant-producto" class="form-control border-0 shadow-sm py-2 text-center fw-bold text-primary" value="1" min="1">
                        </div>
                        <div class="col-7 col-md-3">
                            <button type="button" id="add-order-line" class="btn btn-dark w-100 rounded-3 shadow-sm py-2 fw-bold transition-hover">
                                <i class="fa-solid fa-plus me-1"></i> Añadir
                            </button>
                        </div>
                    </div>

                    <div id="panel-opciones-producto" class="bg-white p-3 rounded-4 border border-primary border-opacity-25 shadow-sm mb-4 d-none">
                        <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                            <h6 class="fw-bold text-primary mb-0"><i class="fa-solid fa-sliders me-2"></i>Personalizacion</h6>
                            <span class="badge text-bg-light" id="customization-surcharge">+ L. 0.00</span>
                        </div>
                        <div class="order-customization-groups" id="order-customization-groups"></div>
                    </div>

                    <div class="table-responsive flex-grow-1 border rounded-3 mb-4">
                        <table class="table table-hover align-middle mb-0 editable-order-table" id="tabla-detalles">
                            <thead class="table-light text-muted small text-uppercase">
                                <tr>
                                    <th class="ps-3 py-3" style="min-width: 200px;">Producto</th>
                                    <th class="py-3" style="min-width: 250px;">Personalización</th>
                                    <th class="text-center py-3">Cant.</th>
                                    <th class="text-end py-3">Subtotal</th>
                                    <th class="text-end pe-3 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="border-top-0">
                            </tbody>
                        </table>
                    </div>

                    <div class="bg-light p-4 rounded-4 mt-auto border border-primary border-opacity-10 shadow-sm">
                        <div class="row align-items-center">
                            <div class="col-md-7">
                                <div class="row g-3">
                                    <div class="col-12 col-sm-4">
                                        <label class="small fw-bold text-muted mb-1"><i class="fa-solid fa-wallet me-1"></i> Estado de Pago</label>
                                        <select name="tipo_pago" id="tipo_pago" class="form-select border-0 shadow-sm py-2 fw-medium" onchange="gestionarPago()">
                                            <option value="Pendiente">Dejar Pendiente</option>
                                            <option value="Abonado">Anticipo / Abono</option>
                                            <option value="Pagado">Liquidado (Total)</option>
                                        </select>
                                    </div>
                                    <div class="col-12 col-sm-4" id="contenedor-abono" style="display: none;">
                                        <label class="small fw-bold text-muted mb-1">Monto del Abono</label>
                                        <div class="input-group shadow-sm rounded-3 overflow-hidden">
                                            <span class="input-group-text bg-white border-0 text-muted fw-bold">L.</span>
                                            <input type="number" name="monto_abono" id="monto_abono" class="form-control border-0 py-2 text-success fw-bold" step="0.01" value="0">
                                        </div>
                                    </div>
                                    <div class="col-12 col-sm-4" id="contenedor-metodo-pago" style="display: none;">
                                        <label class="small fw-bold text-muted mb-1">Metodo de Pago</label>
                                        <select name="metodo_pago" id="metodo_pago" class="form-select border-0 shadow-sm py-2 fw-medium">
                                            <option value="Efectivo">Efectivo</option>
                                            <option value="Transferencia">Transferencia</option>
                                            <option value="Tarjeta">Tarjeta</option>
                                            <option value="Otro">Otro</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-5 text-md-end mt-4 mt-md-0 border-start-md">
                                <span class="text-muted small fw-bold text-uppercase tracking-wide d-block mb-1">Total a Pagar</span>
                                <h2 class="fw-black text-primary mb-0 display-6">L. <span id="total-pedido">0.00</span></h2>
                                <input type="hidden" name="total_final" id="input-total" value="0">
                            </div>
                        </div>
                    </div>

                    <button type="submit" id="btnGuardarPedido" class="btn btn-success btn-lg rounded-pill fw-bold mt-4 shadow w-100 py-3 transition-hover fs-5">
                        <i class="fa-solid fa-check-double me-2"></i> Confirmar y Guardar Pedido
                    </button>
                </div>
            </div>            
        </div>
    </form>
</div>

<script type="application/json" id="order-configurations"><?= json_encode($configuraciones, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?></script>

<div class="modal fade" id="modalCliente" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 bg-primary bg-opacity-10 rounded-top-4">
                <h5 class="modal-title fw-bold text-primary"><i class="fa-solid fa-user-plus me-2"></i>Registrar Nuevo Cliente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="api.php?resource=clientes&action=guardar" method="POST">
                <div class="modal-body p-4">
                    <div class="form-floating mb-3">
                        <input type="text" name="nombre" id="nombreCliente" class="form-control bg-light border-0" required placeholder="Ej: Juan Pérez">
                        <label for="nombreCliente" class="text-muted">Nombre Completo</label>
                    </div>
                    <div class="form-floating mb-3">
                        <input type="text" name="telefono" id="telCliente" class="form-control bg-light border-0" required placeholder="0000-0000">
                        <label for="telCliente" class="text-muted">Teléfono</label>
                    </div>
                    <div class="form-floating mb-0">
                        <input type="email" name="email" id="emailCliente" class="form-control bg-light border-0" placeholder="cliente@correo.com">
                        <label for="emailCliente" class="text-muted">Correo Electrónico (Opcional)</label>
                    </div>
                </div>
                <div class="modal-footer border-0 px-4 pb-4 pt-0">
                    <button type="submit" class="btn btn-primary w-100 rounded-pill py-3 fw-bold shadow-sm">Guardar Cliente</button>
                </div>
            </form>
        </div>
    </div>
</div>

<link rel="stylesheet" href="css/views/pedidos-nuevo.css?v=<?= filemtime(__DIR__ . '/../../public/css/views/pedidos-nuevo.css') ?>">

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.26.25"></script>
<script src="js/views/pedidos-nuevo.js?v=<?= filemtime(__DIR__ . '/../../public/js/views/pedidos-nuevo.js') ?>"></script>
