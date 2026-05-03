<?php
require_once '../app/controllers/PedidoController.php';
$pedidosCtrl = new PedidoController();

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
</div>

<?php foreach($listado as $ped): ?>
<div class="modal fade" id="modalDetalle<?= $ped['id_pedido'] ?>" tabindex="-1" aria-hidden="true">
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
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $detalles = $pedidosCtrl->obtenerDetalles($ped['id_pedido']);
                            foreach($detalles as $det): 
                            ?>
                            <tr>
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
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>