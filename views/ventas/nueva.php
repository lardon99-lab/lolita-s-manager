
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
                </div>
            </div>
        </div>
    </div>
</div>
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