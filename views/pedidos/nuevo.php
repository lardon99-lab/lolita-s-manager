<div class="container-fluid p-4">
    <div class="mb-4">
        <h2 class="fw-bold text-dark"><i class="fa-solid fa-cake-candles me-2 text-primary"></i>Nuevo Pedido Especial</h2>
        <p class="text-muted">Registra los detalles, fecha de entrega y productos personalizados.</p>
    </div>

    <form action="../app/controllers/PedidoController.php?action=crear" method="POST">
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm p-4 rounded-4">
                    <h5 class="fw-bold mb-3">Información General</h5>
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Cliente</label>
                        <div class="input-group">
                            <span class="input-group-text border-0 bg-light"><i class="fa-solid fa-magnifying-glass"></i></span>
                            <input list="lista-clientes" name="id_cliente_search" id="cliente-input" class="form-control border-0 bg-light" placeholder="Escribe nombre del cliente..." required>
                            <datalist id="lista-clientes">
                                <?php foreach($clientes as $c): ?>
                                    <option data-id="<?= $c['id_cliente'] ?>" value="<?= $c['nombre_completo'] ?>">
                                <?php endforeach; ?>
                            </datalist>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCliente">
                                <i class="fa-solid fa-user-plus"></i>
                            </button>
                        </div>
                        <input type="hidden" name="id_cliente" id="id_cliente_real">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Fecha y Hora de Entrega</label>
                        <input type="datetime-local" name="fecha_entrega" class="form-control border-0 bg-light" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Sucursal de Entrega</label>
                        <select name="id_sucursal" class="form-select border-0 bg-light" required>
                            <?php if ($_SESSION['role'] == 'Admin'): ?>
                                <option value="">-- Seleccionar Sucursal --</option>
                            <?php endif; ?>

                            <?php foreach($sucursales as $s): ?>
                                <option value="<?= $s['id_sucursal'] ?>" 
                                    <?= ($_SESSION['id_sucursal'] == $s['id_sucursal']) ? 'selected' : '' ?>>
                                    <?= $s['nombre_sucursal'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted" style="font-size: 0.75rem;">Indica dónde recogerá el cliente su pedido.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Observaciones Generales</label>
                        <textarea name="observaciones" class="form-control border-0 bg-light" rows="3" placeholder="Ej: El cliente recogerá en coche..."></textarea>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card border-0 shadow-sm p-4 rounded-4">
                    <h5 class="fw-bold mb-3">Detalle del Pedido</h5>
                    
                    <div class="row g-2 mb-4">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Producto</label>
                            <select id="select-producto" class="form-select border-0 bg-light">
                                <option value="">Elegir producto...</option>
                                <?php foreach($productos as $p): ?>
                                    <option value="<?= $p['id_producto'] ?>" data-precio="<?= $p['precio_base'] ?>">
                                        <?= $p['nombre_producto'] ?> (L. <?= $p['precio_base'] ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-bold">Cant.</label>
                            <input type="number" id="cant-producto" class="form-control border-0 bg-light" value="1" min="1">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="button" onclick="agregarFila()" class="btn btn-primary w-100 rounded-pill fw-bold">
                                <i class="fa-solid fa-plus me-1"></i> Agregar
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table align-middle" id="tabla-detalles">
                            <thead>
                                <tr class="text-muted small">
                                    <th>Producto</th>
                                    <th>Personalización (Sabor, mensaje, etc)</th>
                                    <th>Cant.</th>
                                    <th>Subtotal</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                </tbody>
                        </table>
                    </div>

                    <div class="card border-0 bg-light p-3 rounded-4 mt-3">
                        <h6 class="fw-bold mb-3">Gestión de Pago</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="small fw-bold">Tipo de Pago</label>
                                <select name="tipo_pago" id="tipo_pago" class="form-select border-0" onchange="gestionarPago()">
                                    <option value="Pendiente">Dejar Pendiente (Saldo total)</option>
                                    <option value="Abonado">Hacer un Abono (Anticipo)</option>
                                    <option value="Pagado">Pago Completo</option>
                                </select>
                            </div>
                            <div class="col-md-6" id="contenedor-abono" style="display: none;">
                                <label class="small fw-bold">Monto del Abono</label>
                                <div class="input-group">
                                    <span class="input-group-text border-0">L. </span>
                                    <input type="number" name="monto_abono" id="monto_abono" class="form-control border-0" step="0.01" value="0">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-3 text-end">
                            <h4 class="fw-bold mb-0">Total: L. <span id="total-pedido">0.00</span></h4>
                            <input type="hidden" name="total_final" id="input-total" value="0">
                            <button type="submit" class="btn btn-success btn-lg rounded-pill px-5 fw-bold mt-3 shadow-sm w-100">
                                <i class="fa-solid fa-floppy-disk me-2"></i> Guardar Pedido
                            </button>
                        </div>
                    </div>
                </div>
            </div>            
        </div>
    </form>

        <div class="modal fade" id="modalCliente" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header border-0">
                        <h5 class="modal-title fw-bold">Registrar Cliente</h5>
                            <button type="button" class="btn-close" data-bs-size="modal" aria-label="Close"></button>
                            </div>
                            <form action="../app/controllers/ClienteController.php?action=guardar" method="POST">
                                <div class="modal-body">
                                    <div class="mb-3">
                                        <label class="small fw-bold">Nombre Completo</label>
                                        <input type="text" name="nombre" class="form-control bg-light border-0" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="small fw-bold">Teléfono</label>
                                        <input type="text" name="telefono" class="form-control bg-light border-0" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="small fw-bold">Email (Opcional)</label>
                                        <input type="email" name="email" class="form-control bg-light border-0">
                                    </div>
                                    </div>
                                    <div class="modal-footer border-0">
                                        <button type="submit" class="btn btn-primary rounded-pill px-4">Guardar Cliente</button>
                                    </div>
                            </form>
                    </div>
                </div>
            </div>
</div>

<script>
    function agregarFila() {
    const select = document.getElementById('select-producto');
    const productoNombre = select.options[select.selectedIndex].text;
    const productoId = select.value;
    const precio = parseFloat(select.options[select.selectedIndex].getAttribute('data-precio'));
    const cantidad = parseInt(document.getElementById('cant-producto').value);

    if (!productoId) return alert("Selecciona un producto");

    const subtotal = precio * cantidad;
    const tabla = document.getElementById('tabla-detalles').getElementsByTagName('tbody')[0];
    const nuevaFila = tabla.insertRow();

    nuevaFila.innerHTML = `
        <td><input type="hidden" name="productos[]" value="${productoId}"><strong>${productoNombre}</strong></td>
        <td><input type="text" name="personalizacion[]" class="form-control form-control-sm border-0 bg-light" placeholder="Ej: Sabor chocolate..."></td>
        <td><input type="hidden" name="cantidades[]" value="${cantidad}">${cantidad}</td>
        <td class="subtotal-fila" data-valor="${subtotal}">L.${subtotal.toFixed(2)}</td>
        <td><button type="button" class="btn btn-sm text-danger" onclick="this.parentElement.parentElement.remove(); calcularTotal();"><i class="fa-solid fa-trash"></i></button></td>
    `;

    calcularTotal(); // Llamamos a la suma
    }

    function calcularTotal() {
        let total = 0;
        // Buscamos todas las celdas de subtotal que tengan la clase 'subtotal-fila'
        document.querySelectorAll('.subtotal-fila').forEach(td => {
            total += parseFloat(td.getAttribute('data-valor'));
        });

        // Actualizamos el texto visual y el input oculto que se envía a PHP
        document.getElementById('total-pedido').innerText = total.toFixed(2);
        document.getElementById('input-total').value = total.toFixed(2);
    }
    function gestionarPago() {
        const tipo = document.getElementById('tipo_pago').value;
        const contenedorAbono = document.getElementById('contenedor-abono');
        const inputAbono = document.getElementById('monto_abono');
        const total = parseFloat(document.getElementById('input-total').value);
        if (tipo === 'Abonado') {
            contenedorAbono.style.display = 'block';
            inputAbono.value = (total / 2).toFixed(2); // Sugerir el 50% por defecto
        } else if (tipo === 'Pagado') {
            contenedorAbono.style.display = 'none';
            inputAbono.value = total;
            } else {
                contenedorAbono.style.display = 'none';
                inputAbono.value = 0;
            }
    }

    

    document.getElementById('cliente-input').addEventListener('input', function(e) {
    const input = e.target;
    const list = document.getElementById('lista-clientes');
    const options = list.options;
    const hiddenInput = document.getElementById('id_cliente_real');
    
    hiddenInput.value = ""; // Reset por seguridad

    for (let i = 0; i < options.length; i++) {
        if (options[i].value === input.value) {
            hiddenInput.value = options[i].getAttribute('data-id');
            break;
        }
    }
    });
</script>