<?php
require_once '../app/controllers/PedidoController.php';
$pedidosCtrl = new PedidoController();

<<<<<<< HEAD
$id_rol = (int)($_SESSION['id_rol'] ?? 0);
$filtro_estado = isset($_GET['estado']) && $_GET['estado'] !== '' ? $_GET['estado'] : 'Pendiente';
$listado = $pedidosCtrl->listarTodos($filtro_estado);
?>

<div class="container-fluid p-3 p-md-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h2 class="fw-black text-dark mb-1">
                <i class="fa-solid fa-box-open me-2 text-primary"></i>Gestión de Pedidos
            </h2>
            <p class="text-muted small mb-0">Monitorea entregas, cobros y estados de producción.</p>
        </div>
        
        <?php if (in_array($id_rol, [1, 3])): ?>
        <div class="d-flex gap-2 flex-wrap">
            <a href="api.php?resource=pedidos&amp;action=reporte_pendientes" target="_blank" rel="noopener" class="btn btn-outline-danger rounded-pill shadow-sm px-3 transition-hover fw-bold d-flex align-items-center">
                <i class="fa-solid fa-file-pdf me-2"></i>PDF Pendientes
            </a>
            <a href="index.php?view=pedidos-nuevo" class="btn btn-primary rounded-pill shadow-sm px-4 transition-hover fw-bold d-flex align-items-center">
                <i class="fa-solid fa-plus me-2"></i>Nuevo Pedido
            </a>
        </div>
        <?php endif; ?>
    </div>

    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3 bg-light rounded-4">
            <div class="row g-3 align-items-center">
                <div class="col-12 col-md-3">
                    <label class="small text-muted fw-bold text-uppercase tracking-wide mb-2 d-block">Estado</label>
                    <select id="filtroEstadoPedido" class="form-select border-0 shadow-sm" onchange="window.location.href='index.php?view=pedidos-lista&estado=' + encodeURIComponent(this.value)">
                        <option value="Pendiente" <?= $filtro_estado === 'Pendiente' ? 'selected' : '' ?>>Pendiente</option>
                        <option value="En Preparación" <?= $filtro_estado === 'En Preparación' ? 'selected' : '' ?>>En Preparación</option>
                        <option value="Listo" <?= $filtro_estado === 'Listo' ? 'selected' : '' ?>>Listo</option>
                        <option value="Entregado" <?= $filtro_estado === 'Entregado' ? 'selected' : '' ?>>Entregado</option>
                        <option value="Cancelado" <?= $filtro_estado === 'Cancelado' ? 'selected' : '' ?>>Cancelado</option>
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
    </div>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
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
                        <td>
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
                                'En Preparación' => ['bg' => 'bg-info', 'text' => 'text-info', 'border' => 'border-info', 'icon' => 'fa-fire-burner'],
                                'Listo' => ['bg' => 'bg-success', 'text' => 'text-success', 'border' => 'border-success', 'icon' => 'fa-check'],
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
                                $puedoGestionar = false;
                                if (in_array($id_rol, [1, 3]) && !in_array($ped['estado'], ['Entregado', 'Cancelado'])) $puedoGestionar = true;
                                if ($id_rol === 2 && $ped['estado'] === 'Listo') $puedoGestionar = true;
                                ?>

                                <?php if ($puedoGestionar): ?>
                                    <button class="btn btn-sm btn-light text-dark rounded-circle shadow-sm border transition-hover" style="width: 35px; height: 35px;"
                                            onclick='gestionarPedido(<?= (int) $ped['id_pedido'] ?>, <?= json_encode($ped['estado'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>, <?= (float) $ped['saldo_pendiente'] ?>, <?= (int) $id_rol ?>)'
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
=======
$filtro_estado = $_GET['estado'] ?? 'Todos';
$listado = $pedidosCtrl->listarTodos($filtro_estado);

// Nota: Eliminé el segundo bloque 'if !isset' ya que el primero arriba ya inicializa la variable correctamente.
?>

<div class="container-fluid p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold text-dark">Gestión de Pedidos Especiales</h2>
        <a href="index.php?view=pedidos-nuevo" class="btn btn-primary rounded-pill px-4 shadow-sm">
            <i class="fa-solid fa-plus me-2"></i>Nuevo Pedido
        </a>
    </div>

    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body d-flex gap-2 flex-wrap">
            <?php 
            $estados = ['Todos', 'Pendiente', 'En Preparación', 'Listo', 'Entregado', 'Cancelado'];
            foreach($estados as $est): 
                $active = ($filtro_estado == $est) ? 'btn-primary' : 'btn-outline-secondary';
            ?>
                <a href="index.php?view=pedidos-lista&estado=<?= urlencode($est) ?>" class="btn btn-sm rounded-pill <?= $active ?> px-3">
                    <?= $est ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="bg-white shadow-sm rounded-4 p-0 overflow-hidden">
        <table class="table align-middle mb-0">
            <thead class="bg-light">
                <tr class="text-muted small">
                    <th class="ps-4">ID</th>
                    <th>Cliente</th>
                    <th>Entrega</th>
                    <th>Pago</th>
                    <th>Total</th>
                    <th>Estado</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($listado as $ped): ?>
                <tr>
                    <td class="ps-4">#<?= $ped['id_pedido'] ?></td>
                    <td>
                        <div class="fw-bold"><?= $ped['nombre_cliente'] ?></div>
                        <small class="text-muted"><?= $ped['telefono'] ?></small>
                    </td>
                    <td>
                        <div class="small fw-bold"><i class="fa-regular fa-calendar me-1"></i> <?= date('d/m/Y', strtotime($ped['fecha_entrega'])) ?></div>
                        <div class="small text-muted"><i class="fa-regular fa-clock me-1"></i> <?= date('H:i', strtotime($ped['fecha_entrega'])) ?></div>
                    </td>
                    <td>
                        <?php if($ped['saldo_pendiente'] > 0): ?>
                            <span class="badge bg-light text-danger border border-danger">Debe: L. <?= number_format($ped['saldo_pendiente'], 2) ?></span>
                        <?php else: ?>
                            <span class="badge bg-light text-success border border-success">Pagado</span>
                        <?php endif; ?>
                    </td>
                    <td class="fw-bold">L. <?= number_format($ped['total_pedido'], 2) ?></td>
                    <td>
                        <?php 
                        $statusClass = [
                            'Pendiente' => 'bg-warning text-dark',
                            'En Preparación' => 'bg-info text-white',
                            'Listo' => 'bg-success text-white',
                            'Entregado' => 'bg-secondary text-white',
                            'Cancelado' => 'bg-danger text-white'
                        ];
                        ?>
                        <span class="badge rounded-pill <?= $statusClass[$ped['estado']] ?? 'bg-light' ?>">
                            <?= $ped['estado'] ?>
                        </span>
                    </td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-outline-primary rounded-pill me-1" data-bs-toggle="modal" data-bs-target="#modalDetalle<?= $ped['id_pedido'] ?>">
                            <i class="fa-solid fa-eye me-1"></i> Detalles
                        </button>
                        
                        <?php if ($ped['estado'] !== 'Entregado' && $ped['estado'] !== 'Cancelado'): ?>
                            <div class="btn-group shadow-sm">
                                <button class="btn btn-sm btn-light dropdown-toggle rounded-pill" data-bs-toggle="dropdown" aria-expanded="false">
                                    Estado
                                </button>
                                <ul class="dropdown-menu shadow border-0">
                                    <li><a class="dropdown-item" href="../app/controllers/PedidoController.php?action=actualizar_estado&id=<?= $ped['id_pedido'] ?>&nuevo_estado=En Proceso&return=pedidos-lista">👨‍🍳 En Proceso</a></li>
                                    <li><a class="dropdown-item" href="../app/controllers/PedidoController.php?action=actualizar_estado&id=<?= $ped['id_pedido'] ?>&nuevo_estado=Listo&return=pedidos-lista">✅ Listo</a></li>
                                    <li class="border-top"><a class="dropdown-item fw-bold text-success" href="../app/controllers/PedidoController.php?action=actualizar_estado&id=<?= $ped['id_pedido'] ?>&nuevo_estado=Entregado&return=pedidos-lista">🎂 Entregado</a></li>
                                    <li class="border-top"><a class="dropdown-item text-danger" href="../app/controllers/PedidoController.php?action=actualizar_estado&id=<?= $ped['id_pedido'] ?>&nuevo_estado=Cancelado&return=pedidos-lista">❌ Cancelar</a></li>
                                </ul>
                            </div>
                        <?php else: ?>
                            <span class="text-muted small italic">
                                <i class="fa-solid fa-lock ms-2"></i> Finalizado
                            </span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
>>>>>>> c6dbe5e6ebab9e6256ac5bf146680a5a83fa3874
</div>

<?php foreach($listado as $ped): ?>
<div class="modal fade" id="modalDetalle<?= $ped['id_pedido'] ?>" tabindex="-1" aria-hidden="true">
<<<<<<< HEAD
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
=======
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-0 bg-light rounded-top-4">
                <h5 class="modal-title fw-bold">Detalles del Pedido #<?= $ped['id_pedido'] ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row mb-4">
                    <div class="col-md-6 border-end">
                        <h6 class="text-muted small fw-bold text-uppercase">Información del Cliente</h6>
                        <p class="mb-1"><strong>Nombre:</strong> <?= $ped['nombre_cliente'] ?></p>
                        <p class="mb-1"><strong>Teléfono:</strong> <?= $ped['telefono'] ?></p>
                        <p class="mb-0 text-primary"><strong>Fecha Entrega:</strong> <?= date('d/m/Y H:i', strtotime($ped['fecha_entrega'])) ?></p>
                    </div>
                    <div class="col-md-6 ps-4">
                        <h6 class="text-muted small fw-bold text-uppercase">Observaciones Generales</h6>
                        <p class="bg-light p-2 rounded small"><?= !empty($ped['observaciones_generales']) ? $ped['observaciones_generales'] : 'Sin observaciones generales.' ?></p>
                    </div>
                </div>

                <h6 class="text-muted small fw-bold text-uppercase mb-3">Productos y Personalización</h6>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead class="bg-light">
                            <tr>
                                <th>Producto</th>
                                <th>Cantidad</th>
                                <th>Personalización / Mensajes</th>
                                <th class="text-end">Subtotal</th>
>>>>>>> c6dbe5e6ebab9e6256ac5bf146680a5a83fa3874
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $detalles = $pedidosCtrl->obtenerDetalles($ped['id_pedido']);
                            foreach($detalles as $det): 
                            ?>
                            <tr>
<<<<<<< HEAD
                                <td class="ps-4 py-3">
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($det['nombre_producto']) ?></div>
                                    <?php if(!empty($det['detalles_personalizacion'])): ?>
                                        <div class="small text-muted mt-1 bg-light p-2 rounded-3 border">
                                            <i class="fa-solid fa-quote-left text-primary opacity-50 me-1"></i>
                                            <?= htmlspecialchars($det['detalles_personalizacion']) ?>
                                        </div>
                                    <?php else: ?>
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
=======
                                <td class="fw-bold"><?= $det['nombre_producto'] ?></td>
                                <td><?= $det['cantidad'] ?></td>
                                <td><span class="text-info fst-italic"><?= !empty($det['detalles_personalizacion']) ? $det['detalles_personalizacion'] : 'Sin mensaje.' ?></span></td>
                                <td class="text-end">$<?= number_format($det['subtotal'], 2) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="3" class="text-end">Monto Abonado:</th>
                                <th class="text-end text-success">-$<?= number_format($ped['monto_abonado'], 2) ?></th>
                            </tr>
                            <tr class="table-light">
                                <th colspan="3" class="text-end h5 fw-bold">Saldo Pendiente:</th>
                                <th class="text-end h5 fw-bold text-danger">$<?= number_format($ped['saldo_pendiente'], 2) ?></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
>>>>>>> c6dbe5e6ebab9e6256ac5bf146680a5a83fa3874
            </div>
        </div>
    </div>
</div>
<<<<<<< HEAD
<?php endforeach; ?>

<link rel="stylesheet" href="css/views/pedidos-lista.css">

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.26.25"></script>
<script src="js/views/pedidos-lista.js"></script>
=======
<?php endforeach; ?>
>>>>>>> c6dbe5e6ebab9e6256ac5bf146680a5a83fa3874
