<?php
// views/pedidos/historial.php
require_once '../app/controllers/PedidoController.php';
$pedidosCtrl = new PedidoController();

$fechaInicio = $_GET['desde'] ?? null;
$fechaFin = $_GET['hasta'] ?? null;

$ventas = $pedidosCtrl->obtenerHistorialVentas($fechaInicio, $fechaFin);
$totalIngresos = array_sum(array_column($ventas, 'total_pedido'));
?>

<div class="container-fluid p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">Historial de Ventas 💰</h2>
        <div class="text-end">
            <span class="text-muted small">Total del periodo:</span>
            <h3 class="text-success fw-bold">L. <?= number_format($totalIngresos, 2) ?></h3>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body">
            <form method="GET" action="index.php" class="row g-3 align-items-end">
                <input type="hidden" name="view" value="ventas-historial">
                <div class="col-md-4">
                    <label class="small fw-bold">Desde:</label>
                    <input type="date" name="desde" class="form-control bg-light border-0" value="<?= $fechaInicio ?>">
                </div>
                <div class="col-md-4">
                    <label class="small fw-bold">Hasta:</label>
                    <input type="date" name="hasta" class="form-control bg-light border-0" value="<?= $fechaFin ?>">
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary rounded-pill w-100 fw-bold">
                        <i class="fa-solid fa-filter me-2"></i> Filtrar Historial
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr class="small text-muted">
                            <th class="ps-4">ID Pedido</th>
                            <th>Fecha Venta</th>
                            <th>Cliente</th>
                            <th>Sucursal</th>
                            <th class="text-end pe-4">Total Pagado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($ventas) > 0): ?>
                            <?php foreach($ventas as $v): ?>
                            <tr>
                                <td class="ps-4">#<?= $v['id_pedido'] ?></td>
                                <td><?= date('d/m/Y', strtotime($v['fecha_registro'])) ?></td>
                                <td><?= $v['cliente'] ?></td>
                                <td><span class="badge bg-light text-dark border"><?= $v['nombre_sucursal'] ?></span></td>
                                <td class="text-end pe-4 fw-bold text-success">L. <?= number_format($v['total_pedido'], 2) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">No hay ventas registradas en este periodo.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>