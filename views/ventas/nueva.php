<?php
require_once '../app/controllers/InventarioController.php';

$invCtrl = new InventarioController();

$id_sucursal_user = (int) ($_SESSION['id_sucursal'] ?? 0) ?: null;
$requestedBranch = !empty($_GET['sucursal_id']) ? (int) $_GET['sucursal_id'] : null;
if ($requestedBranch && \App\Security\Auth::canAccessBranch($requestedBranch, 'sales.create')) $id_sucursal_user = $requestedBranch;
$allowedBranches = \App\Security\Auth::allowedBranches('sales.create');
$db = (new Database())->getConnection();
if ($allowedBranches === null) {
    $sucursales = $db->query("SELECT id_sucursal, nombre_sucursal FROM sucursales WHERE estado = 'Activa' ORDER BY nombre_sucursal")->fetchAll(PDO::FETCH_ASSOC);
} elseif ($allowedBranches === []) {
    $sucursales = [];
} else {
    $holders = implode(',', array_fill(0, count($allowedBranches), '?'));
    $stmtBranches = $db->prepare("SELECT id_sucursal, nombre_sucursal FROM sucursales WHERE estado = 'Activa' AND id_sucursal IN ($holders) ORDER BY nombre_sucursal");
    $stmtBranches->execute($allowedBranches);
    $sucursales = $stmtBranches->fetchAll(PDO::FETCH_ASSOC);
}
if ($id_sucursal_user === null && count($sucursales) === 1) $id_sucursal_user = (int) $sucursales[0]['id_sucursal'];
$productos = $id_sucursal_user ? $invCtrl->listarProductosDisponibles($id_sucursal_user) : [];
?>

<div class="container-fluid p-2 p-md-4">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-8 col-xl-7">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-dark p-4 border-0">
                    <div class="d-flex align-items-center">
                        <div class="bg-primary text-white rounded-3 p-2 me-3 shadow-sm">
                            <i class="fa-solid fa-cart-shopping fs-4"></i>
                        </div>
                        <div>
                            <h4 class="mb-0 fw-bold text-white">Punto de Venta</h4>
                            <p class="small mb-0 text-white-50">Registro de transacciones directas</p>
                        </div>
                    </div>
                </div>

                <div class="card-body p-3 p-md-4 bg-white">
                    
                    <?php if (count($sucursales) > 1 || empty($_SESSION['id_sucursal'])): ?>
                        <div class="p-3 bg-light rounded-3 mb-4 border-start border-primary border-4 shadow-sm">
                            <label class="form-label small fw-bold text-primary text-uppercase mb-2" style="letter-spacing: 1px;">Sucursal de Despacho</label>
                            <select class="form-select border-0 shadow-sm fw-bold text-dark py-2" onchange="location.href='index.php?view=ventas-nueva&sucursal_id=' + this.value">
                                <option value="">-- Seleccione Sucursal --</option>
                                <?php foreach($sucursales as $s): ?>
                                    <option value="<?= $s['id_sucursal'] ?>" <?= ($id_sucursal_user == $s['id_sucursal']) ? 'selected' : '' ?>>
                                        <?= e($s['nombre_sucursal']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <?php if (!$id_sucursal_user): ?>
                        <div class="text-center py-5">
                            <div class="mb-3 display-4 text-muted opacity-25">
                                <i class="fa-solid fa-store"></i>
                            </div>
                            <h5 class="text-muted fw-normal">Esperando selección de sucursal...</h5>
                        </div>
                    <?php else: ?>
                        
                        <form id="formNuevaVenta">
                            <input type="hidden" name="id_sucursal" id="id_sucursal" value="<?= $id_sucursal_user ?>">

                            <div class="mb-4">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-2" style="letter-spacing: 0.5px;">Buscar Producto</label>
                                <div class="input-group input-group-lg shadow-sm rounded-3 overflow-hidden border">
                                    <span class="input-group-text bg-white border-0"><i class="fa-solid fa-magnifying-glass text-primary"></i></span>
                                    <input type="text" id="buscador-producto" class="form-control border-0 ps-0 shadow-none" placeholder="Escriba el nombre..." style="font-size: 1rem;">
                                </div>
                                
                                <select name="id_producto" id="id_producto" class="form-select border-0 bg-light mt-3 rounded-3 custom-select-list shadow-inner" size="4" required>
                                    <?php if (empty($productos)): ?>
                                        <option value="" disabled class="text-danger">🚫 Sin stock disponible en esta sucursal</option>
                                    <?php else: ?>
                                        <?php foreach($productos as $p): ?>
                                            <option value="<?= $p['id_producto'] ?>" 
                                                    data-precio="<?= $p['precio_base'] ?>" 
                                                    data-stock="<?= $p['stock'] ?>"
                                                    class="py-2 px-3 border-bottom">
                                                <?= e($p['nombre_producto']) ?> — (Stock: <?= (int) $p['stock'] ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                                <div id="no-results" class="alert alert-warning small mt-2 py-2 d-none border-0 shadow-sm">
                                    <i class="fa-solid fa-circle-exclamation me-2"></i>No hay coincidencias.
                                </div>
                            </div>

                            <div class="row g-3 mb-4 align-items-end">
                                <div class="col-12 col-md-4">
                                    <label class="form-label small fw-bold text-muted text-uppercase">Cantidad</label>
                                    <input type="number" id="cantidad" min="1" class="form-control form-control-lg border shadow-sm fw-bold text-center" value="1">
                                </div>
                                <div class="col-12 col-md-4">
                                    <label class="form-label small fw-bold text-muted text-uppercase">Precio Unidad</label>
                                    <div class="h3 mb-0 text-primary fw-bold p-2 bg-light rounded-3 text-center border" id="label-precio">L. 0.00</div>
                                </div>
                                <div class="col-12 col-md-4">
                                    <button type="button" id="btnAgregarCarrito" class="btn btn-primary btn-lg w-100 shadow-sm fw-bold py-3 rounded-3">
                                        <i class="fa-solid fa-plus me-2"></i>AGREGAR
                                    </button>
                                </div>
                            </div>

                            <hr class="my-4 opacity-10">

                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-0">Carrito de Compras</label>
                                <span class="badge bg-light text-dark border shadow-sm px-3" id="items-count">0 Items</span>
                            </div>
                            
                            <div class="table-responsive mb-4 bg-white rounded-3 border shadow-sm">
                                <table class="table table-hover align-middle mb-0 sales-cart-table">
                                    <thead class="bg-light">
                                        <tr class="small text-muted text-uppercase">
                                            <th class="ps-3 py-3">Producto</th>
                                            <th class="text-center py-3">Cant.</th>
                                            <th class="text-end py-3">Precio</th>
                                            <th class="text-end py-3">Subtotal</th>
                                            <th class="text-center py-3"><i class="fa-solid fa-trash-can"></i></th>
                                        </tr>
                                    </thead>
                                    <tbody id="cuerpoCarrito">
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-5">
                                                <i class="fa-solid fa-basket-shopping fs-2 mb-3 d-block opacity-25"></i>
                                                El carrito está vacío
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="p-4 bg-dark rounded-4 shadow-lg border-top border-primary border-4">
                                
                                <div class="mb-4 p-3 rounded-3 border border-secondary border-opacity-25" style="background: rgba(255,255,255,0.02);">
                                    <label class="form-label small fw-bold text-white-50 text-uppercase mb-3 d-block text-center text-md-start">Método de Pago</label>
                                    <div class="d-flex gap-3">
                                        <input type="radio" class="btn-check" name="metodo_pago" id="pago_efectivo" value="Efectivo" checked>
                                        <label class="btn btn-outline-light flex-fill fw-bold py-2 border-opacity-50" for="pago_efectivo">
                                            <i class="fa-solid fa-money-bill-wave text-success me-2"></i>Efectivo
                                        </label>

                                        <input type="radio" class="btn-check" name="metodo_pago" id="pago_tarjeta" value="Tarjeta">
                                        <label class="btn btn-outline-light flex-fill fw-bold py-2 border-opacity-50" for="pago_tarjeta">
                                            <i class="fa-solid fa-credit-card text-info me-2"></i>Tarjeta
                                        </label>
                                    </div>
                                </div>
                                <div class="row align-items-center">
                                    <div class="col-12 col-md-7 mb-3 mb-md-0 text-center text-md-start">
                                        <span class="text-white-50 small text-uppercase fw-bold tracking-wider">Total Neto a Pagar</span>
                                        <div class="h1 mb-0 text-success fw-bold" id="total-venta" style="font-size: 2.5rem;">L. 0.00</div>
                                    </div>
                                    <div class="col-12 col-md-5">
                                        <button type="button" id="btnCobrar" class="btn btn-success btn-lg rounded-pill px-4 shadow py-3 w-100 fw-bold border-2 border-white border-opacity-10" disabled>
                                            <i class="fa-solid fa-cash-register me-2"></i>FINALIZAR COBRO
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<link rel="stylesheet" href="css/views/ventas-nueva.css?v=<?= filemtime(__DIR__ . '/../../public/css/views/ventas-nueva.css') ?>">

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.26.25"></script>
<script src="js/views/ventas-nueva.js"></script>
