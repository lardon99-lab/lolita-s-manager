<?php
require_once '../app/controllers/PedidoController.php';
$pedidosCtrl = new PedidoController();

// 1. Establecer la fecha de HOY por defecto para el control de caja diario
$hoy = date('Y-m-d');
$desde = !empty($_GET['desde']) ? \App\Http\Validator::date($_GET['desde'], 'fecha desde') : $hoy;
$hasta = !empty($_GET['hasta']) ? \App\Http\Validator::date($_GET['hasta'], 'fecha hasta') : $hoy;
$tipo = !empty($_GET['tipo']) ? \App\Http\Validator::enum($_GET['tipo'], ['Pedido', 'Venta'], 'tipo') : null;

$database = new Database();
$db = $database->getConnection();
$allowedBranches = \App\Security\Auth::allowedBranches('reports.view');
if ($allowedBranches === null) {
    $listaSucursales = $db->query("SELECT id_sucursal, nombre_sucursal FROM sucursales WHERE estado = 'Activa' ORDER BY nombre_sucursal")->fetchAll(PDO::FETCH_ASSOC);
} elseif ($allowedBranches === []) {
    $listaSucursales = [];
} else {
    $holders = implode(',', array_fill(0, count($allowedBranches), '?'));
    $branchStmt = $db->prepare("SELECT id_sucursal, nombre_sucursal FROM sucursales WHERE estado = 'Activa' AND id_sucursal IN ($holders) ORDER BY nombre_sucursal");
    $branchStmt->execute($allowedBranches);
    $listaSucursales = $branchStmt->fetchAll(PDO::FETCH_ASSOC);
}
$esAdmin = count($listaSucursales) > 1;

// 3. Configuramos los filtros asegurando la sucursal asignada para los empleados
$filtros = [
    'desde'    => $desde,
    'hasta'    => $hasta,
    'busqueda' => !empty($_GET['busqueda']) ? \App\Http\Validator::text($_GET['busqueda'], 'busqueda', 100) : null,
    'sucursal' => !empty($_GET['sucursal']) ? (int) $_GET['sucursal'] : null,
    'tipo'     => $tipo
];

// Obtención de datos filtrados para la vista general
$ventas = $pedidosCtrl->obtenerHistorialVentas($filtros);
$mermas = $pedidosCtrl->obtenerMermas($filtros);

// Cálculos de Caja Reales
$resumenIngresos = $pedidosCtrl->obtenerResumenIngresosCaja($filtros);
$totalIngresos = $resumenIngresos['total'];
$totalGastos = array_sum(array_column($mermas, 'monto'));
$totalCajaReal = $totalIngresos - $totalGastos;
?>

<div class="container-fluid app-page p-2 p-md-4">
    <header class="app-page-header">
        <div class="app-page-header__main">
            <span class="app-page-header__icon" aria-hidden="true"><i class="fa-solid fa-chart-column"></i></span>
            <div class="app-page-header__copy">
                <h2 class="app-page-header__title">Explorador de ventas</h2>
                <p class="app-page-header__subtitle">Historial de transacciones y flujo de caja.</p>
            </div>
        </div>
            <div class="app-page-header__actions">
                <div class="history-balance text-start">
                <div class="d-flex justify-content-between align-items-center mb-1 gap-4">
                    <span class="small fw-bold text-uppercase text-muted">Total en caja real</span>
                    <?php if (\App\Security\Auth::hasPermission('cash.adjust')): ?><button type="button" class="btn btn-warning btn-sm rounded-pill fw-bold" data-bs-toggle="modal" data-bs-target="#modalMerma">
                        <i class="fa-solid fa-minus-circle me-1"></i> Registrar Gasto
                    </button><?php endif; ?>
                </div>
                    <strong class="history-balance__value">L. <?= number_format($totalCajaReal, 2) ?></strong>
                    <small class="text-muted mt-1 d-block">Cobrado: L. <?= number_format($totalIngresos, 2) ?></small>
                    <?php if($totalGastos > 0): ?>
                        <small class="text-danger mt-1 d-block"><i class="fa-solid fa-arrow-trend-down me-1"></i> - L. <?= number_format($totalGastos, 2) ?> en gastos</small>
                    <?php endif; ?>
                </div>
            </div>
    </header>

    <div class="app-toolbar">
            <form method="GET" action="index.php" class="row g-3 align-items-end">
                <input type="hidden" name="view" value="ventas-historial">
                
                <div class="col-12 col-md-4 col-xl">
                    <label class="small fw-bold mb-1 text-muted text-uppercase">Buscar:</label>
                    <div class="input-group shadow-sm rounded-3 border-0 overflow-hidden">
                        <span class="input-group-text bg-white border-0 px-2"><i class="fa-solid fa-magnifying-glass text-primary"></i></span>
                        <input type="text" name="busqueda" class="form-control bg-white border-0 py-2 px-1" placeholder="Cliente o #" value="<?= htmlspecialchars($filtros['busqueda'] ?? '') ?>">
                    </div>
                </div>

                <?php if($esAdmin): ?>
                <div class="col-12 col-md-4 col-xl">
                    <label class="small fw-bold mb-1 text-muted text-uppercase">Sucursal:</label>
                    <select name="sucursal" class="form-select border-0 shadow-sm py-2">
                        <option value="">Todas</option>
                        <?php foreach($listaSucursales as $suc): ?>
                            <option value="<?= (int) $suc['id_sucursal'] ?>" <?= ($filtros['sucursal'] == $suc['id_sucursal']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($suc['nombre_sucursal']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>

                <div class="col-12 col-md-4 col-xl">
                    <label class="small fw-bold mb-1 text-muted text-uppercase">Tipo:</label>
                    <select name="tipo" class="form-select border-0 shadow-sm py-2">
                        <option value="">Todos</option>
                        <option value="Pedido" <?= $filtros['tipo'] == 'Pedido' ? 'selected' : '' ?>>Reservas</option>
                        <option value="Venta" <?= $filtros['tipo'] == 'Venta' ? 'selected' : '' ?>>Mostrador</option>
                    </select>
                </div>

                <div class="col-6 col-md-6 col-xl">
                    <label class="small fw-bold mb-1 text-muted text-uppercase">Desde:</label>
                    <input type="date" name="desde" class="form-control border-0 shadow-sm py-2" value="<?= e($filtros['desde']) ?>">
                </div>

                <div class="col-6 col-md-6 col-xl">
                    <label class="small fw-bold mb-1 text-muted text-uppercase">Hasta:</label>
                    <input type="date" name="hasta" class="form-control border-0 shadow-sm py-2" value="<?= e($filtros['hasta']) ?>">
                </div>

                <div class="col-12 col-xl-auto mt-3 mt-xl-0">
                    <div class="d-flex flex-column flex-sm-row gap-2">
                        
                        <button type="submit" class="btn btn-primary rounded-3 fw-bold shadow-sm px-4 py-2 text-uppercase">
                            Filtrar
                        </button>
                        
                        <div class="d-flex gap-2">
                            <?php 
                            $paramsPdf = $filtros;
                            $paramsPdf['desde'] = $filtros['desde'] ?? $hoy; 
                            $paramsPdf['hasta'] = $filtros['hasta'] ?? $hoy; 
                            $urlPdf = "exportar_pdf.php?" . http_build_query($paramsPdf); 
                            ?>
                            <button type="button" id="btnExportarPdf" class="btn btn-dark rounded-3 shadow-sm px-3 py-2 flex-fill d-flex align-items-center justify-content-center" data-base-url="<?= e($urlPdf) ?>" data-current-branch="<?= e($filtros['sucursal'] ?? '') ?>" data-branches="<?= e(json_encode($listaSucursales, JSON_UNESCAPED_UNICODE)) ?>" title="Exportar Control Diario">
                                <i class="fa-solid fa-file-pdf text-danger"></i>
                            </button>

                            <a href="index.php?view=ventas-historial" class="btn btn-outline-dark bg-white rounded-3 shadow-sm px-3 py-2 flex-fill d-flex align-items-center justify-content-center border-0" style="border: 1px solid #dee2e6 !important;" title="Limpiar Filtros">
                                <i class="fa-solid fa-rotate-left"></i>
                            </a>
                        </div>

                    </div>
                </div>
            </form>
    </div>

    <div class="card app-panel overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 mobile-card-table history-main-table">
                <thead class="bg-dark text-white text-uppercase small">
                    <tr>
                        <th class="ps-4 py-3" style="width: 80px;">ID</th>
                        <th style="width: 150px;">Origen</th>
                        <th>Fecha y Hora</th>
                        <th>Cliente / Sucursal</th>
                        <th>Productos</th>
                        <th class="text-end pe-4">Monto Final</th>
                    </tr>
                </thead>
                <tbody class="bg-white">
                    <?php if(!empty($ventas)): ?>
                        <?php foreach($ventas as $v): ?>
                        <tr>
                            <td class="ps-4" data-label="ID">
                                <span class="badge bg-dark px-2 py-2 rounded-2 shadow-sm">#<?= $v['id_pedido'] ?></span>
                            </td>
                            
                            <td data-label="Origen">
                                <?php if($v['tipo'] == 'Pedido Especial'): ?>
                                    <small class="d-block text-primary fw-bold text-uppercase" style="font-size: 0.65rem;">Reserva</small>
                                    <span class="text-dark fw-medium"><i class="fa-solid fa-calendar-check text-primary me-1"></i> Pedido</span>
                                <?php else: ?>
                                    <small class="d-block text-info fw-bold text-uppercase" style="font-size: 0.65rem;">Mostrador</small>
                                    <span class="text-dark fw-medium"><i class="fa-solid fa-cash-register text-info me-1"></i> Venta</span>
                                <?php endif; ?>
                            </td>
                            
                            <td data-label="Fecha y hora">
                                <div class="fw-bold text-dark"><?= date('d/m/Y', strtotime($v['fecha_registro'])) ?></div>
                                <div class="text-muted small"><i class="fa-regular fa-clock me-1"></i><?= date('g:i a', strtotime($v['fecha_registro'])) ?></div>
                            </td>
                            
                            <td data-label="Cliente / Sucursal">
                                <div class="fw-bold text-dark text-truncate" style="max-width: 150px;">
                                    <?= !empty($v['cliente']) ? htmlspecialchars($v['cliente']) : 'Público General' ?>
                                </div>
                                <div class="small text-muted">
                                    <i class="fa-solid fa-store me-1 text-primary"></i><?= e($v['nombre_sucursal']) ?>
                                </div>
                            </td>

                            <td data-label="Productos">
                                <div class="small text-muted text-truncate" style="max-width: 250px;" title="<?= e($v['productos']) ?>">
                                    <?= e($v['productos']) ?>
                                </div>
                            </td>
                            
                            <td class="text-end pe-4" data-label="Monto final">
                                <span class="fw-bold text-dark font-monospace fs-5">
                                    L. <?= number_format($v['total_pedido'], 2) ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="fa-solid fa-folder-open fs-1 d-block mb-3 opacity-25"></i>
                                No se encontraron registros para la fecha o filtros seleccionados.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if (\App\Security\Auth::hasPermission('cash.adjust')): ?>
<div class="modal fade" id="modalMerma" tabindex="-1" aria-labelledby="modalMermaLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header bg-dark text-white border-0">
                <h5 class="modal-title fw-bold" id="modalMermaLabel"><i class="fa-solid fa-money-bill-transfer me-2 text-warning"></i>Registrar Salida de Caja</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="api.php?resource=pedidos&action=guardarMerma" method="POST">
                <div class="modal-body p-4">
                    <p class="text-muted small mb-4">Este monto será descontado directamente del total registrado en caja.</p>
                    
                    <div class="mb-3">
                        <label class="fw-bold small text-uppercase text-muted mb-1">Monto (L.)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0">L.</span>
                            <input type="number" step="0.01" min="0.01" name="monto_merma" class="form-control bg-light border-start-0" required placeholder="0.00">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="fw-bold small text-uppercase text-muted mb-1">Motivo</label>
                        <select name="motivo_merma" class="form-select bg-light" required>
                            <option value="" disabled selected>Seleccione una categoría...</option>
                            <option value="Pago a Proveedores">📦 Pago a Proveedores</option>
                            <option value="Gastos de Insumos">🛒 Gastos de Insumos</option>
                            <option value="Pagos a Terceros">🤝 Pagos a Terceros</option>
                        </select>
                    </div>

                    <?php if ($esAdmin): ?>
                        <div class="mb-3">
                            <label class="fw-bold small text-uppercase text-muted mb-1">Sucursal afectada</label>
                            <select name="id_sucursal" class="form-select bg-light" required>
                                <option value="" disabled selected>🏢 Seleccione una sucursal...</option>
                                <?php foreach($listaSucursales as $suc): ?>
                                    <option value="<?= $suc['id_sucursal'] ?>">📍 <?= htmlspecialchars($suc['nombre_sucursal']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="fw-bold small text-uppercase text-muted mb-1">Descripción (Opcional)</label>
                        <textarea name="descripcion_merma" class="form-control bg-light" rows="2" placeholder="Detalles de la salida..."></textarea>
                    </div>
                </div>

                <div class="modal-footer border-0 pt-0 px-4 pb-4">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning rounded-pill px-4 fw-bold shadow-sm">Guardar Merma</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.26.25"></script>
<script src="js/views/historial.js"></script>

<link rel="stylesheet" href="css/views/historial.css?v=<?= filemtime(__DIR__ . '/../../public/css/views/historial.css') ?>">
