<?php
?>

<div class="container-fluid app-page p-2 p-md-4">
    <header class="app-page-header">
        <div class="app-page-header__main">
            <span class="app-page-header__icon" aria-hidden="true"><i class="fa-solid fa-box-open"></i></span>
            <div class="app-page-header__copy">
            <h2 class="app-page-header__title">Gestión de pedidos</h2>
            <p class="app-page-header__subtitle">Monitorea entregas, cobros y estados de producción.</p>
            </div>
        </div>
        
        <?php if (\App\Security\Auth::hasPermission('reports.view') || \App\Security\Auth::hasPermission('orders.create')): ?>
        <div class="app-page-header__actions page-actions">
            <?php if (\App\Security\Auth::hasPermission('reports.view')): ?>
            <a href="api.php?resource=pedidos&amp;action=reporte_pendientes" target="_blank" rel="noopener" class="btn btn-outline-danger rounded-pill shadow-sm px-3 transition-hover fw-bold d-flex align-items-center">
                <i class="fa-solid fa-file-pdf me-2"></i>PDF Pendientes
            </a>
            <?php endif; ?>
            <?php if (\App\Security\Auth::hasPermission('orders.create')): ?>
            <a href="index.php?view=pedidos-nuevo" class="btn btn-primary rounded-pill shadow-sm px-4 transition-hover fw-bold d-flex align-items-center">
                <i class="fa-solid fa-plus me-2"></i>Nuevo Pedido
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </header>

    <div class="app-toolbar">
            <div class="row g-3 align-items-center">
                <div class="col-12 col-md-3">
                    <label class="small text-muted fw-bold text-uppercase tracking-wide mb-2 d-block" for="filtroEstadoPedido">Estado</label>
                    <select id="filtroEstadoPedido" class="form-select border-0 shadow-sm" onchange="window.location.href='index.php?view=pedidos-lista&estado=' + encodeURIComponent(this.value)">
                        <option value="Pendiente" <?= $filtro_estado === 'Pendiente' ? 'selected' : '' ?>>Pendiente</option>
                        <option value="Terminado" <?= $filtro_estado === 'Terminado' ? 'selected' : '' ?>>Terminado</option>
                        <option value="Entregado" <?= $filtro_estado === 'Entregado' ? 'selected' : '' ?>>Entregado</option>
                        <option value="Todos" <?= $filtro_estado === 'Todos' ? 'selected' : '' ?>>Todos</option>
                    </select>
                </div>
                <div class="col-12 col-md-9">
                    <div class="d-flex flex-wrap gap-2 align-items-center justify-content-md-end">
                        <span class="chip-pill"><i class="fa-solid fa-filter"></i> Vista actual: <strong class="ms-1"><?= htmlspecialchars($filtro_estado) ?></strong></span>
                    </div>
                </div>
            </div>
    </div>

    <div class="card app-panel overflow-hidden mb-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 mobile-card-table orders-main-table">
                <thead class="bg-light">
                    <tr class="text-muted small text-uppercase tracking-wide">
                        <th class="ps-4 py-3">ID</th>
                        <th class="py-3">Cliente</th>
                        <th class="py-3">Fecha Entrega</th>
                        <th class="py-3">Finanzas</th>
                        <th class="py-3">Estado</th>
                        <th class="text-center py-3 pe-4">Acciones</th>
                    </tr>
                </thead>
                <tbody class="border-top-0">
                    <?php if (empty($listado)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                                    <i class="fa-solid fa-box-open fa-2x text-muted opacity-50"></i>
                                </div>
                                <h5 class="fw-bold mb-1">Sin resultados</h5>
                                <p class="small mb-0">No se encontraron pedidos con el estado: <strong><?= e($filtro_estado) ?></strong></p>
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach($listado as $ped): ?>
                    <tr class="bg-white">
                        <td class="ps-4 fw-black text-primary fs-6" data-label="ID">#<?= $ped['id_pedido'] ?></td>
                        <td data-label="Cliente">
                            <div class="fw-bold text-dark fs-6"><?= htmlspecialchars($ped['nombre_cliente']) ?></div>
                            <div class="small text-muted mt-1">
                                <a href="https://wa.me/504<?= preg_replace('/\D+/', '', (string) $ped['telefono']) ?>" target="_blank" rel="noopener" class="text-decoration-none text-muted transition-hover d-inline-block">
                                    <i class="fa-brands fa-whatsapp text-success me-1"></i><?= e($ped['telefono']) ?>
                                </a>
                            </div>
                        </td>
                        <td data-label="Entrega">
                            <div class="d-flex align-items-center">
                                <div class="border border-primary border-opacity-25 rounded-3 text-center me-3 shadow-sm bg-white" style="min-width: 55px; overflow: hidden;">
                                    <div class="small fw-bold text-uppercase bg-primary text-white py-1" style="font-size: 0.65rem; letter-spacing: 1px;">
                                        <?= date('M', strtotime($ped['fecha_entrega'])) ?>
                                    </div>
                                    <div class="h5 fw-black mb-0 py-1 text-dark">
                                        <?= date('d', strtotime($ped['fecha_entrega'])) ?>
                                    </div>
                                </div>
                                <div>
                                    <div class="small fw-bold text-dark mb-1">
                                        <i class="fa-regular fa-clock text-primary me-1"></i><?= date('g:i a', strtotime($ped['fecha_entrega'])) ?>
                                    </div>
                                    <div class="small text-muted"><?= date('Y', strtotime($ped['fecha_entrega'])) ?></div>
                                </div>
                            </div>
                        </td>
                        <td data-label="Finanzas">
                            <div class="small mb-2 text-muted">Total: <strong class="text-dark">L. <?= number_format($ped['total_pedido'], 2) ?></strong></div>
                            <?php if($ped['saldo_pendiente'] > 0): ?>
                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 rounded-pill px-2 py-1">
                                    Debe: L. <?= number_format($ped['saldo_pendiente'], 2) ?>
                                </span>
                            <?php else: ?>
                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-2 py-1">
                                    <i class="fa-solid fa-check-double me-1"></i>Pagado
                                </span>
                            <?php endif; ?>
                        </td>
                        <td data-label="Estado">
                            <?php 
                            $statusConfig = [
                                'Pendiente' => ['bg' => 'bg-warning', 'text' => 'text-dark', 'border' => 'border-warning', 'icon' => 'fa-clock'],
                                'Terminado' => ['bg' => 'bg-success', 'text' => 'text-success', 'border' => 'border-success', 'icon' => 'fa-check'],
                                'Entregado' => ['bg' => 'bg-secondary', 'text' => 'text-secondary', 'border' => 'border-secondary', 'icon' => 'fa-box-archive'],
                                'Cancelado' => ['bg' => 'bg-danger', 'text' => 'text-danger', 'border' => 'border-danger', 'icon' => 'fa-xmark']
                            ];
                            $conf = $statusConfig[$ped['estado']] ?? ['bg' => 'bg-light', 'text' => 'text-dark', 'border' => 'border-dark', 'icon' => 'fa-circle'];
                            ?>
                            <span class="badge <?= $conf['bg'] ?> bg-opacity-10 <?= $conf['text'] ?> border <?= $conf['border'] ?> border-opacity-25 rounded-pill px-3 py-2 fw-bold">
                                <i class="fa-solid <?= e($conf['icon']) ?> me-1"></i><?= e($ped['estado']) ?>
                            </span>
                        </td>
                        <td class="text-center pe-4" data-label="Acciones">
                            <div class="d-flex justify-content-center gap-2">
                                <button class="btn btn-sm btn-light text-primary rounded-circle shadow-sm border transition-hover" style="width: 35px; height: 35px;" data-bs-toggle="modal" data-bs-target="#modalDetalle<?= $ped['id_pedido'] ?>" title="Ver Detalles">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                                
                                <?php 
                                $transiciones = [];
                                foreach (\App\Security\OrderStatusPolicy::transitions((string) $ped['estado']) as $destino => $permiso) {
                                    $rol = (int) ($_SESSION['id_rol'] ?? 0);
                                    if (\App\Security\OrderStatusPolicy::roleCanTransition($rol, (string) $ped['estado'], $destino)
                                        && \App\Security\Auth::hasPermission($permiso)) {
                                        $transiciones[] = $destino;
                                    }
                                }
                                $puedoGestionar = $transiciones !== [];
                                ?>

                                <?php if ($puedoGestionar): ?>
                                    <button class="btn btn-sm btn-light text-dark rounded-circle shadow-sm border transition-hover" style="width: 35px; height: 35px;"
                                            onclick='gestionarPedido(<?= (int) $ped['id_pedido'] ?>, <?= json_encode($ped['estado'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>, <?= (float) $ped['saldo_pendiente'] ?>, <?= json_encode($transiciones, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'
                                            title="Gestionar Estado">
                                        <i class="fa-solid fa-gear"></i>
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-sm btn-light text-muted rounded-circle border opacity-50" style="width: 35px; height: 35px; cursor: not-allowed;" title="No disponible">
                                        <i class="fa-solid fa-lock"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php foreach($listado as $ped): ?>
<div class="modal fade" id="modalDetalle<?= $ped['id_pedido'] ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-fullscreen-sm-down">
        <div class="modal-content border-0 shadow-lg rounded-4">
            
            <div class="modal-header border-bottom bg-light rounded-top-4 pb-3 pt-3">
                <div class="d-flex align-items-center">
                    <div class="bg-primary p-2 rounded-circle shadow-sm me-3 d-flex justify-content-center align-items-center" style="width: 45px; height: 45px;">
                        <i class="fa-solid fa-receipt text-white fs-5"></i>
                    </div>
                    <div>
                        <h4 class="modal-title fw-black text-dark mb-0">Pedido #<?= $ped['id_pedido'] ?></h4>
                        <span class="small text-muted"><i class="fa-regular fa-calendar me-1"></i> Registrado el <?= date('d/m/Y H:i', strtotime($ped['fecha_creacion'] ?? 'now')) ?></span>
                    </div>
                </div>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
            </div>
            
            <div class="modal-body p-4">
                <div class="row g-3 mb-4 bg-white p-3 rounded-4 border shadow-sm">
                    <div class="col-sm-6 col-md-4">
                        <label class="small text-muted fw-bold text-uppercase tracking-wide d-block mb-1">Cliente</label>
                        <span class="fw-bold text-dark fs-6 d-block text-truncate" title="<?= htmlspecialchars($ped['nombre_cliente']) ?>"><?= htmlspecialchars($ped['nombre_cliente']) ?></span>
                    </div>
                    <div class="col-sm-6 col-md-4 border-start-md">
                        <label class="small text-muted fw-bold text-uppercase tracking-wide d-block mb-1">Contacto</label>
                        <a href="https://wa.me/504<?= preg_replace('/\D+/', '', (string) $ped['telefono']) ?>" target="_blank" rel="noopener" class="text-decoration-none fw-bold text-dark transition-hover d-inline-block">
                            <i class="fa-brands fa-whatsapp text-success me-1"></i><?= e($ped['telefono']) ?>
                        </a>
                    </div>
                    <div class="col-sm-12 col-md-4 border-start-md text-md-end mt-3 mt-md-0">
                        <label class="small text-muted fw-bold text-uppercase tracking-wide d-block mb-1">Entrega Programada</label>
                        
                        <span class="badge bg-primary text-white shadow-sm px-3 py-2 fs-6 rounded-pill">
                            <i class="fa-regular fa-clock me-1"></i> <?= date('d/m/Y - h:i A', strtotime($ped['fecha_entrega'])) ?>
                        </span>
                        
                    </div>
                </div>

                <h6 class="fw-bold mb-3 text-dark"><i class="fa-solid fa-list-check me-2 text-primary"></i>Detalle de Productos</h6>
                <div class="table-responsive rounded-4 border mb-4">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light text-muted small text-uppercase tracking-wide">
                            <tr>
                                <th class="ps-4 py-2 border-bottom-0">Producto y Personalización</th>
                                <th class="text-center py-2 border-bottom-0">Cant.</th>
                                <th class="text-end pe-4 py-2 border-bottom-0">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $detalles = $pedidosCtrl->obtenerDetalles($ped['id_pedido']);
                            foreach($detalles as $det): 
                            ?>
                            <tr>
                                <td class="ps-4 py-3">
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($det['nombre_producto']) ?></div>
                                    <?php if(!empty($det['opciones_personalizacion'])): ?>
                                        <div class="small text-primary mt-1"><i class="fa-solid fa-sliders me-1"></i><?= e($det['opciones_personalizacion']) ?></div>
                                    <?php endif; ?>
                                    <?php if(!empty($det['id_diseno'])): ?>
                                        <div class="order-design-summary mt-2">
                                            <div class="small fw-bold text-dark"><i class="fa-solid fa-palette text-primary me-1"></i>Diseno personalizado</div>
                                            <?php if(!empty($det['color_descripcion'])): ?><div class="small text-muted">Color: <?= e($det['color_descripcion']) ?></div><?php endif; ?>
                                            <?php if(!empty($det['frase'])): ?><div class="small text-muted">Frase: <?= e($det['frase']) ?></div><?php endif; ?>
                                            <?php if(!empty($det['instrucciones'])): ?><div class="small text-muted"><?= e($det['instrucciones']) ?></div><?php endif; ?>
                                            <?php if(!empty($det['archivo_nombre_interno'])): ?>
                                                <a class="btn btn-sm btn-outline-primary mt-2" href="api.php?resource=pedidos&amp;action=ver_diseno&amp;id=<?= (int) $det['id_diseno'] ?>" target="_blank" rel="noopener">
                                                    <i class="fa-solid fa-image me-1" aria-hidden="true"></i>Ver referencia
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if(!empty($det['detalles_personalizacion'])): ?>
                                        <div class="small text-muted mt-1 bg-light p-2 rounded-3 border">
                                            <i class="fa-solid fa-quote-left text-primary opacity-50 me-1"></i>
                                            <?= htmlspecialchars($det['detalles_personalizacion']) ?>
                                        </div>
                                    <?php elseif(empty($det['opciones_personalizacion'])): ?>
                                        <div class="small text-muted fst-italic mt-1">Sin especificaciones extra.</div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 30px; height: 30px; font-size: 0.9rem;">
                                        <?= $det['cantidad'] ?>
                                    </span>
                                </td>
                                <td class="text-end pe-4 fw-bold text-dark">L. <?= number_format($det['subtotal'], 2) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="row gx-4">
                    <div class="col-md-6 order-2 order-md-1">
                        <?php if(!empty($ped['observaciones_generales'])): ?>
                        <div class="mt-2">
                            <label class="small fw-bold text-muted text-uppercase tracking-wide d-block mb-2">Notas Especiales</label>
                            <div class="bg-warning bg-opacity-10 border border-warning border-opacity-25 rounded-4 p-3 small text-dark shadow-sm">
                                <i class="fa-solid fa-circle-exclamation text-warning me-1"></i>
                                <?= nl2br(htmlspecialchars($ped['observaciones_generales'])) ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6 order-1 order-md-2 mb-4 mb-md-0">
                        <div class="bg-light p-3 rounded-4 border">
                            <div class="d-flex justify-content-between mb-2 small">
                                <span class="text-muted fw-bold">Total del Pedido:</span>
                                <span class="fw-bold text-dark">L. <?= number_format($ped['total_pedido'], 2) ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-3 small border-bottom pb-3">
                                <span class="text-muted fw-bold">Monto Abonado:</span>
                                <span class="fw-bold text-success">- L. <?= number_format($ped['monto_abonado'], 2) ?></span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-danger fw-bold text-uppercase tracking-wide small">Saldo Pendiente:</span>
                                <span class="fs-4 fw-black text-danger">L. <?= number_format($ped['saldo_pendiente'], 2) ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer border-0 bg-light rounded-bottom-4 px-4 py-3 border-top">
                <button type="button" class="btn btn-outline-secondary rounded-pill px-4 fw-bold" data-bs-dismiss="modal">Cerrar</button>
                <button onclick="window.print()" class="btn btn-dark rounded-pill px-4 shadow-sm fw-bold transition-hover d-flex align-items-center">
                    <i class="fa-solid fa-print me-2"></i>Imprimir Ticket
                </button>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>

<link rel="stylesheet" href="css/views/pedidos-lista.css?v=<?= filemtime(__DIR__ . '/../../public/css/views/pedidos-lista.css') ?>">

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.26.25"></script>
<script src="js/views/pedidos-lista.js?v=<?= filemtime(__DIR__ . '/../../public/js/views/pedidos-lista.js') ?>"></script>
