    // Se ejecuta de forma segura cuando la página ya cargó
    document.addEventListener("DOMContentLoaded", function() {
        const selectProducto = document.getElementById('select-producto');
        
        if (selectProducto) {
            selectProducto.addEventListener('change', function() {
                const optionSelect = this.options[this.selectedIndex];
                const panelPastel = document.getElementById('panel-opciones-pastel');

                // Ocultar si vuelve a "Selecciona un producto..."
                if (this.value === "") {
                    panelPastel.style.display = 'none';
                    return;
                }

                // Capturamos el nombre de la categoría y el nombre del producto
                const categoria = String(optionSelect.getAttribute('data-categoria') || '').trim();
                const nombreProducto = String(optionSelect.getAttribute('data-nombre') || '').trim();

                // Validación infalible: Busca la palabra "pastel" en la categoría O en el nombre
                if (categoria.includes('pastel') || nombreProducto.includes('pastel')) {
                    panelPastel.style.display = 'block';
                } else {
                    panelPastel.style.display = 'none';
                    // Resetear selects al ocultar
                    document.getElementById('extra-masa').selectedIndex = 0;
                    document.getElementById('extra-relleno').selectedIndex = 0;
                    document.getElementById('extra-cubierta').selectedIndex = 0;
                }
            });
        }
    });

    function agregarFila() {
        const select = document.getElementById('select-producto');
        const inputExtraDiseno = document.getElementById('costo-extra');
        const panelPastel = document.getElementById('panel-opciones-pastel');
        
        if (select.selectedIndex <= 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Atención',
                text: 'Por favor selecciona un producto de la lista primero.',
                confirmButtonColor: '#ff85a2'
            });
            return;
        }

        const productoNombre = select.options[select.selectedIndex].text.split('(')[0].trim();
        const productoId = select.value;
        const precioBase = parseFloat(select.options[select.selectedIndex].getAttribute('data-precio'));
        let costoExtraDiseno = parseFloat(inputExtraDiseno.value) || 0; 
        const cantidad = parseInt(document.getElementById('cant-producto').value);

        // Variables para los extras del pastel
        let costoOpcionesPastel = 0;
        let textoPersonalizacion = '';

        // Si el panel de pastel está visible, calculamos los extras
        if (panelPastel.style.display === 'block') {
            const masa = document.getElementById('extra-masa').value.split('|');
            const relleno = document.getElementById('extra-relleno').value.split('|');
            const cubierta = document.getElementById('extra-cubierta').value.split('|');

            costoOpcionesPastel = parseFloat(masa[0]) + parseFloat(relleno[0]) + parseFloat(cubierta[0]);
            
            // Solo agregar texto de personalización si el usuario cambió al menos una de las bases
            if(parseFloat(masa[0]) > 0 || parseFloat(relleno[0]) > 0 || parseFloat(cubierta[0]) > 0) {
                textoPersonalizacion = `[Masa: ${masa[1]} | Relleno: ${relleno[1]} | Cubierta: ${cubierta[1]}]\n`;
            }
        }

        // Sumar diseño + opciones del pastel
        const costoExtraTotal = costoExtraDiseno + costoOpcionesPastel;
        const precioUnitarioReal = precioBase + costoExtraTotal;
        const subtotal = precioUnitarioReal * cantidad;

        const tabla = document.getElementById('tabla-detalles').querySelector('tbody');
        const nuevaFila = tabla.insertRow();
        nuevaFila.classList.add('bg-white'); 

        let badgeExtra = costoExtraTotal > 0 ? `<span class="badge bg-warning bg-opacity-25 text-dark border border-warning rounded-pill mt-1 px-2"><i class="fa-solid fa-wand-magic-sparkles text-warning me-1"></i>+ L. ${costoExtraTotal.toFixed(2)} extras</span>` : '';

        nuevaFila.innerHTML = `
            <td class="ps-3 py-3">
                <input type="hidden" name="productos[]" value="${productoId}">
                <input type="hidden" name="costos_extras[]" value="${costoExtraTotal}">
                <div class="fw-bold text-dark fs-6">${productoNombre}</div>
                <div class="small text-muted mt-1">Base: L. ${precioBase.toFixed(2)}</div>
                ${badgeExtra}
            </td>
            <td class="py-3">
                <textarea name="personalizacion[]" class="form-control form-control-sm border-0 bg-light shadow-sm" rows="2" placeholder="Colores, dedicatoria, detalles especiales...">${textoPersonalizacion}</textarea>
            </td>
            <td class="text-center align-middle py-3">
                <input type="hidden" name="cantidades[]" value="${cantidad}">
                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 32px; height: 32px; font-size: 0.9rem;">
                    ${cantidad}
                </span>
            </td>
            <td class="subtotal-fila text-end fw-black align-middle text-success fs-6 py-3" data-valor="${subtotal}">
                L. ${subtotal.toLocaleString('en-US', {minimumFractionDigits: 2})}
            </td>
            <td class="text-end pe-3 align-middle py-3">
                <button type="button" class="btn btn-sm btn-light text-danger rounded-circle shadow-sm border" style="width: 32px; height: 32px;" onclick="this.closest('tr').remove(); calcularTotal();" title="Eliminar fila">
                    <i class="fa-solid fa-trash-can"></i>
                </button>
            </td>
        `;

        // Limpiar selección para el siguiente producto
        select.value = "";
        inputExtraDiseno.value = "0";
        document.getElementById('cant-producto').value = 1;
        panelPastel.style.display = 'none';
        
        document.getElementById('extra-masa').selectedIndex = 0;
        document.getElementById('extra-relleno').selectedIndex = 0;
        document.getElementById('extra-cubierta').selectedIndex = 0;
        
        calcularTotal();
    }

    function calcularTotal() {
        let total = 0;
        document.querySelectorAll('.subtotal-fila').forEach(td => {
            total += parseFloat(td.getAttribute('data-valor'));
        });

        document.getElementById('total-pedido').innerText = total.toLocaleString('en-US', {minimumFractionDigits: 2});
        document.getElementById('input-total').value = total.toFixed(2);

        gestionarPago(); 
    }

    function gestionarPago() {
        const tipo = document.getElementById('tipo_pago').value;
        const contenedorAbono = document.getElementById('contenedor-abono');
        const inputAbono = document.getElementById('monto_abono');
        const total = parseFloat(document.getElementById('input-total').value) || 0;

        if (tipo === 'Abonado') {
            contenedorAbono.style.display = 'block';
            if(parseFloat(inputAbono.value) === 0 || parseFloat(inputAbono.value) > total) {
                inputAbono.value = (total * 0.5).toFixed(2); 
            }
        } else if (tipo === 'Pagado') {
            contenedorAbono.style.display = 'none';
            inputAbono.value = total.toFixed(2);
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
        
        hiddenInput.value = ""; 
        for (let i = 0; i < options.length; i++) {
            if (options[i].value === input.value) {
                hiddenInput.value = options[i].getAttribute('data-id');
                break;
            }
        }
    });

    document.getElementById('formNuevoPedido').addEventListener('submit', function(e) {
        const clienteId = document.getElementById('id_cliente_real').value;
        const total = parseFloat(document.getElementById('input-total').value) || 0;

        if (!clienteId) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Cliente no válido',
                text: 'Por favor, selecciona un cliente válido de la lista sugerida o crea uno nuevo.',
                confirmButtonColor: '#ff85a2'
            });
            return;
        }

        if (total === 0) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Pedido vacío',
                text: 'Debes añadir al menos un producto al detalle del pedido.',
                confirmButtonColor: '#ff85a2'
            });
            return;
        }
    });
