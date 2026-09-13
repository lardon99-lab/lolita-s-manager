document.addEventListener('DOMContentLoaded', function() {
    const selectProd = document.getElementById('id_producto');
    const inputCant = document.getElementById('cantidad');
    const labelPrecio = document.getElementById('label-precio');
    const buscador = document.getElementById('buscador-producto');
    const options = Array.from(selectProd?.options || []);
    const noResults = document.getElementById('no-results');

    const btnAgregar = document.getElementById('btnAgregarCarrito');
    const cuerpoCarrito = document.getElementById('cuerpoCarrito');
    const btnCobrar = document.getElementById('btnCobrar');
    const labelTotal = document.getElementById('total-venta');
    
    let carrito = [];

    if(!selectProd) return;

    function actualizarInterfaz() {
        const option = selectProd.options[selectProd.selectedIndex];
        if (!option || option.value === "") {
            labelPrecio.innerText = "L. 0.00";
            return;
        }
        const precio = parseFloat(option.dataset.precio) || 0;
        const stockDisponible = parseInt(option.dataset.stock) || 0;
        labelPrecio.innerText = `L. ${precio.toLocaleString('en-US', {minimumFractionDigits: 2})}`;
        inputCant.setAttribute('max', stockDisponible);
    }

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

    btnAgregar.addEventListener('click', function() {
        const option = selectProd.options[selectProd.selectedIndex];
        if (!option || option.value === "") {
            Swal.fire('Atención', 'Seleccione un producto primero', 'warning');
            return;
        }
        const id = option.value;
        const nombre = option.text.split('—')[0].trim();
        const precio = parseFloat(option.dataset.precio);
        const stock = parseInt(option.dataset.stock);
        const cantidad = parseInt(inputCant.value);

        if (isNaN(cantidad) || cantidad <= 0) {
            Swal.fire('Atención', 'Ingrese una cantidad válida', 'warning');
            return;
        }
        if (cantidad > stock) {
            Swal.fire('Atención', 'La cantidad supera el stock disponible', 'warning');
            return;
        }

        const existe = carrito.find(item => item.id === id);
        if (existe) {
            if ((existe.cantidad + cantidad) > stock) {
                Swal.fire('Atención', 'No hay suficiente stock para sumar esta cantidad', 'warning');
                return;
            }
            existe.cantidad += cantidad;
            existe.subtotal = existe.cantidad * existe.precio;
        } else {
            carrito.push({
                id: id,
                nombre: nombre,
                precio: precio,
                cantidad: cantidad,
                subtotal: cantidad * precio
            });
        }
        inputCant.value = 1;
        actualizarTablaCarrito();
    });

    function actualizarTablaCarrito() {
        cuerpoCarrito.innerHTML = '';
        let total = 0;
        document.getElementById('items-count').innerText = `${carrito.length} Items`;

        if (carrito.length === 0) {
            cuerpoCarrito.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-5"><i class="fa-solid fa-basket-shopping fs-2 mb-3 d-block opacity-25"></i>El carrito está vacío</td></tr>';
            btnCobrar.disabled = true;
            labelTotal.innerText = "L. 0.00";
            return;
        }

        carrito.forEach((item, index) => {
            total += item.subtotal;
            const row = document.createElement('tr');
            const productCell = document.createElement('td');
            productCell.className = 'ps-3 py-3 align-middle fw-bold text-dark';
            productCell.textContent = item.nombre;

            const quantityCell = document.createElement('td');
            quantityCell.className = 'text-center align-middle';
            const quantityBadge = document.createElement('span');
            quantityBadge.className = 'badge bg-light text-dark border px-3';
            quantityBadge.textContent = String(item.cantidad);
            quantityCell.appendChild(quantityBadge);

            const priceCell = document.createElement('td');
            priceCell.className = 'text-end align-middle text-muted';
            priceCell.textContent = `L. ${item.precio.toFixed(2)}`;
            const subtotalCell = document.createElement('td');
            subtotalCell.className = 'text-end fw-bold align-middle text-primary';
            subtotalCell.textContent = `L. ${item.subtotal.toFixed(2)}`;

            const actionCell = document.createElement('td');
            actionCell.className = 'text-center align-middle';
            const removeButton = document.createElement('button');
            removeButton.type = 'button';
            removeButton.className = 'btn btn-sm btn-outline-danger border-0';
            removeButton.title = 'Eliminar producto';
            removeButton.innerHTML = '<i class="fa-solid fa-trash" aria-hidden="true"></i>';
            removeButton.addEventListener('click', () => {
                carrito.splice(index, 1);
                actualizarTablaCarrito();
            });
            actionCell.appendChild(removeButton);
            row.append(productCell, quantityCell, priceCell, subtotalCell, actionCell);
            cuerpoCarrito.appendChild(row);
        });

        labelTotal.innerText = `L. ${total.toLocaleString('en-US', {minimumFractionDigits: 2})}`;
        btnCobrar.disabled = false;
    }

    btnCobrar.addEventListener('click', function() {
        // Capturar el método de pago seleccionado
        const metodoPago = document.querySelector('input[name="metodo_pago"]:checked').value;

        Swal.fire({
            title: '¿Confirmar Venta?',
            text: `Se cobrará un total de ${labelTotal.innerText} en ${metodoPago}`, // Actualizamos el texto
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, finalizar',
            cancelButtonText: 'Revisar'
        }).then((result) => {
            if (result.isConfirmed) {
                btnCobrar.disabled = true;
                btnCobrar.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Procesando...';
                
                const idSucursal = document.getElementById('id_sucursal').value;
                
                // Agregamos el método de pago al payload JSON
                const payload = { 
                    id_sucursal: idSucursal, 
                    productos: carrito,
                    metodo_pago: metodoPago 
                };

                fetch('api.php?resource=ventas&action=procesarVenta', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify(payload)
                })
                .then(res => res.json())
                .then(data => {
                    if(data.status === 'success') {
                        Swal.fire({ title: '¡Venta Exitosa!', text: data.message, icon: 'success', timer: 1500, showConfirmButton: false }).then(() => location.reload());
                    } else {
                        Swal.fire('Error', data.message, 'error');
                        btnCobrar.disabled = false;
                        btnCobrar.innerHTML = '<i class="fa-solid fa-cash-register me-2"></i>FINALIZAR COBRO';
                    }
                })
                .catch(error => {
                    Swal.fire('Error', 'Fallo de conexión', 'error');
                    btnCobrar.disabled = false;
                    btnCobrar.innerHTML = '<i class="fa-solid fa-cash-register me-2"></i>FINALIZAR COBRO';
                });
            }
        });
    });
    actualizarInterfaz();
});
