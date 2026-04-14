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
                        <select name="id_cliente" class="form-select border-0 bg-light" required>
                            <option value="">Seleccionar cliente...</option>
                            <?php foreach($clientes as $c): ?>
                                <option value="<?= $c['id_cliente'] ?>"><?= $c['nombre_completo'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Fecha y Hora de Entrega</label>
                        <input type="datetime-local" name="fecha_entrega" class="form-control border-0 bg-light" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Sucursal de Retiro</label>
                        <input type="text" class="form-control border-0 bg-light" value="<?= $_SESSION['nombre_sucursal'] ?? 'Sucursal Actual' ?>" readonly>
                        <input type="hidden" name="id_sucursal" value="<?= $_SESSION['id_sucursal'] ?>">
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
                                        <?= $p['nombre_producto'] ?> ($<?= $p['precio_base'] ?>)
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

                    <div class="text-end mt-3">
                        <h4 class="fw-bold">Total: $<span id="total-pedido">0.00</span></h4>
                        <input type="hidden" name="total_final" id="input-total" value="0">
                        <button type="submit" class="btn btn-success btn-lg rounded-pill px-5 fw-bold mt-3 shadow-sm">
                            Guardar Pedido
                        </button>
                    </div>
                </div>
            </div>            
        </div>
    </form>
    <button type="button" class="btn btn-sm btn-outline-primary rounded-pill mt-2" data-bs-toggle="modal" data-bs-target="#modalCliente">
            + Nuevo Cliente
    </button>

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
            <td>
                <input type="hidden" name="productos[]" value="${productoId}">
                <strong>${productoNombre}</strong>
            </td>
            <td>
                <input type="text" name="personalizacion[]" class="form-control form-control-sm border-0 bg-light" placeholder="Ej: Feliz Cumple Juan">
            </td>
            <td>
                <input type="hidden" name="cantidades[]" value="${cantidad}">
                ${cantidad}
            </td>
            <td>$${subtotal.toFixed(2)}</td>
            <td><button type="button" class="btn btn-sm text-danger" onclick="this.parentElement.parentElement.remove(); calcularTotal();"><i class="fa-solid fa-trash"></i></button></td>
        `;

        calcularTotal();
    }

    function calcularTotal() {
        // Lógica simple para sumar subtotales y actualizar el span #total-pedido
    }
</script>