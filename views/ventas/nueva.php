<<<<<<< HEAD
<?php
require_once '../app/controllers/InventarioController.php';

$invCtrl = new InventarioController();

$id_sucursal_user = $_SESSION['id_sucursal'] ?? null;
$rol_user = (int)($_SESSION['id_rol'] ?? 0);

if (($rol_user === 1 || $rol_user === 3) && isset($_GET['sucursal_id']) && !empty($_GET['sucursal_id'])) {
    $requestedBranch = (int) $_GET['sucursal_id'];
    $id_sucursal_user = \App\Security\Auth::canAccessBranch($requestedBranch) ? $requestedBranch : null;
}

$productos = ($id_sucursal_user) ? $invCtrl->listarProductosDisponibles($id_sucursal_user) : [];
$inventoryPage = (new \App\Services\InventarioPageService((new Database())->getConnection()))->data(null);
$sucursales = ($rol_user === 1 || $rol_user === 3) ? $inventoryPage['sucursales'] : [];
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
                    
                    <?php if ($rol_user === 1 || $rol_user === 3): ?>
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
                                <table class="table table-hover align-middle mb-0">
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
=======

<div class="container-fluid p-4">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-primary text-white p-4 rounded-top-4">
                    <h4 class="mb-0 fw-bold"><i class="fa-solid fa-cart-shopping me-2"></i>Nueva Venta</h4>
                </div>
                <div class="card-body p-4">
                    <form id="formNuevaVenta">
                        <div class="mb-4">

                            <label class="form-label small fw-bold text-muted">Buscar Producto</label>
                            <div class="input-group mb-2">
                                <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                                <input type="text" id="buscador-producto" class="form-control border-start-0 ps-0 shadow-none" placeholder="Escribe el nombre del pastel...">
                            </div>
                            
                            <select name="id_producto" id="id_producto" class="form-select border-0 bg-light p-3 rounded-3" size="5" required>
                                <?php foreach($productos as $p): ?>
                                    <option value="<?= $p['id_producto'] ?>" 
                                            data-precio="<?= $p['precio_base'] ?>" 
                                            data-stock="<?= $p['stock'] ?>">
                                        <?= $p['nombre_producto'] ?> (Stock: <?= $p['stock'] ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div id="no-results" class="text-danger small mt-2 d-none">No se encontraron productos.</div>

                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label class="form-label small fw-bold">Cantidad</label>
                                <input type="number" name="cantidad" id="cantidad" min="1" 
                                    class="form-control border-0 bg-light p-3 rounded-3" value="1" required>
                                <div class="invalid-feedback">
                                    No puedes vender más de lo que hay en stock.
                                </div>
                            </div>
                            <div class="col-md-6 mb-4">
                                <label class="form-label small fw-bold">Precio Unitario</label>
                                <div class="h3 mt-2 text-primary fw-bold" id="label-precio">L. 0.00</div>
                            </div>
                        </div>

                        <hr class="text-muted">

                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <div>
                                <span class="text-muted small d-block">Total a cobrar:</span>
                                <span class="h2 fw-bold text-success" id="total-venta">L. 0.00</span>
                            </div>
                            <button type="submit" class="btn btn-primary rounded-pill px-5 py-3 shadow">
                                <i class="fa-solid fa-check me-2"></i>Finalizar Venta
                            </button>
                        </div>
                    </form>
>>>>>>> c6dbe5e6ebab9e6256ac5bf146680a5a83fa3874
                </div>
            </div>
        </div>
    </div>
</div>
<<<<<<< HEAD

<link rel="stylesheet" href="css/views/ventas-nueva.css">

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.26.25"></script>
<script src="js/views/ventas-nueva.js"></script>
=======
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // --- ELEMENTOS ---
    const selectProd = document.getElementById('id_producto');
    const inputCant = document.getElementById('cantidad');
    const labelPrecio = document.getElementById('label-precio');
    const labelTotal = document.getElementById('total-venta');
    const btnVenta = document.querySelector('button[type="submit"]');
    const buscador = document.getElementById('buscador-producto');
    const options = Array.from(selectProd.options);
    const noResults = document.getElementById('no-results');
    const formVenta = document.getElementById('formNuevaVenta');

    // --- FUNCIONES DE LÓGICA ---

    function actualizarInterfaz() {
        const option = selectProd.options[selectProd.selectedIndex];
        
        if (!option || option.value === "") {
            labelPrecio.innerText = "L. 0.00";
            labelTotal.innerText = "L. 0.00";
            return;
        }

        const precio = parseFloat(option.dataset.precio) || 0;
        const stockDisponible = parseInt(option.dataset.stock) || 0;
        const cantidadSolicitada = parseInt(inputCant.value) || 0;

        // 1. Calcular Totales
        labelPrecio.innerText = `L. ${precio.toFixed(2)}`;
        labelTotal.innerText = `L. ${(precio * cantidadSolicitada).toFixed(2)}`;

        // 2. Validar Stock
        inputCant.setAttribute('max', stockDisponible);

        if (cantidadSolicitada > stockDisponible) {
            inputCant.classList.add('is-invalid');
            btnVenta.disabled = true;
            btnVenta.innerHTML = '<i class="fa-solid fa-triangle-exclamation me-2"></i> Stock Insuficiente';
        } else if (cantidadSolicitada <= 0) {
            btnVenta.disabled = true;
            btnVenta.innerHTML = '<i class="fa-solid fa-check me-2"></i> Finalizar Venta';
        } else {
            inputCant.classList.remove('is-invalid');
            btnVenta.disabled = false;
            btnVenta.innerHTML = '<i class="fa-solid fa-check me-2"></i> Finalizar Venta';
        }
    }

    // --- EVENTOS (Buscador y Selección) ---

    buscador.addEventListener('input', function() {
        const filtro = buscador.value.toLowerCase();
        let encontrados = 0;

        options.forEach(option => {
            if (option.value === "") return;
            const texto = option.text.toLowerCase();
            if (texto.includes(filtro)) {
                option.style.display = 'block';
                encontrados++;
            } else {
                option.style.display = 'none';
            }
        });

        noResults.classList.toggle('d-none', encontrados > 0);
        
        if (filtro !== "" && encontrados > 0) {
            const firstVisible = options.find(opt => opt.style.display !== 'none' && opt.value !== "");
            if (firstVisible) selectProd.value = firstVisible.value;
        }
        
        actualizarInterfaz();
    });

    selectProd.addEventListener('change', actualizarInterfaz);
    inputCant.addEventListener('input', actualizarInterfaz);

    // --- ENVÍO DE FORMULARIO CON SWEETALERT2 ---

    formVenta.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const totalTexto = labelTotal.innerText;

        Swal.fire({
            title: '¿Confirmar Venta?',
            text: `Se registrará la venta por un total de ${totalTexto}`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#0d6efd',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, cobrar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                // Deshabilitar botón mientras procesa
                btnVenta.disabled = true;
                btnVenta.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Procesando...';

                const formData = new FormData(this);
                
                fetch('../app/controllers/VentaController.php?action=procesarVenta', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if(data.status === 'success') {
                        Swal.fire({
                            title: '¡Venta Realizada!',
                            text: data.message,
                            icon: 'success',
                            timer: 2000,
                            showConfirmButton: false,
                            timerProgressBar: true
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire('Error', data.message, 'error');
                        actualizarInterfaz(); // Reactiva el botón
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire('Error Crítico', 'No se pudo conectar con el servidor', 'error');
                    actualizarInterfaz();
                });
            }
        });
    });
});
</script>
>>>>>>> c6dbe5e6ebab9e6256ac5bf146680a5a83fa3874
