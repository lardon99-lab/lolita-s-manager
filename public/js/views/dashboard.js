document.addEventListener('DOMContentLoaded', function() {
    // ==============================================================================
    // JS EXISTENTE: REGISTRAR MERMA
    // ==============================================================================
    const botonesMerma = document.querySelectorAll('.btn-mermar-caducado');
    botonesMerma.forEach(boton => {
        boton.addEventListener('click', function(e) {
            e.preventDefault();
            const idInventario = this.getAttribute('data-id');
            const nombreProducto = this.getAttribute('data-nombre');

            Swal.fire({
                title: '¿Retirar lote caducado?',
                text: `Se registrará todo el stock de "${nombreProducto}" como merma por caducidad y se fijará en 0 unidades.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e63946',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, registrar merma',
                cancelButtonText: 'Cancelar',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Procesando...',
                        text: 'Actualizando stock e insertando reporte histórico.',
                        allowOutsideClick: false,
                        didOpen: () => { Swal.showLoading(); }
                    });

                    const formData = new FormData();
                    formData.append('action', 'registrar_merma');
                    formData.append('id_inventario', idInventario);

                    fetch('api.php?resource=inventario', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            Swal.fire({
                                title: '¡Retirado con éxito!',
                                text: data.message,
                                icon: 'success',
                                confirmButtonColor: '#20c997'
                            }).then(() => { location.reload(); });
                        } else {
                            Swal.fire({
                                title: 'Operación fallida',
                                text: data.message,
                                icon: 'error',
                                confirmButtonColor: '#6c757d'
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            title: 'Error de Red',
                            text: 'No se pudo establecer comunicación con el servidor.',
                            icon: 'error',
                            confirmButtonColor: '#6c757d'
                        });
                    });
                }
            });
        });
    });

    // ==============================================================================
    // NUEVO JS: ABASTECER PRODUCTO DIRECTAMENTE
    // ==============================================================================
    const botonesAbastecer = document.querySelectorAll('.btn-abastecer-directo');
    botonesAbastecer.forEach(boton => {
        boton.addEventListener('click', function(e) {
            e.preventDefault();
            
            const idInventario = this.getAttribute('data-id');
            const idProducto = this.getAttribute('data-producto');
            const idSucursal = this.getAttribute('data-sucursal');
            const nombreProducto = this.getAttribute('data-nombre');
            const nombreSucursal = this.getAttribute('data-sucursal-nombre');

            Swal.fire({
                title: 'Abastecer Producto',
                html: `Ingresa la cantidad que deseas añadir al stock de:<br><strong>${nombreProducto}</strong> (${nombreSucursal})`,
                icon: 'info',
                input: 'number',
                inputAttributes: {
                    min: 1,
                    step: 1,
                    placeholder: 'Cantidad en unidades'
                },
                showCancelButton: true,
                confirmButtonColor: '#20c997',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="fa-solid fa-plus me-1"></i> Añadir Stock',
                cancelButtonText: 'Cancelar',
                reverseButtons: true,
                inputValidator: (value) => {
                    if (!value || parseInt(value) <= 0) {
                        return '¡Debes ingresar una cantidad válida mayor a cero!';
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const cantidadAnadir = result.value;

                    // Alerta de carga asíncrona
                    Swal.fire({
                        title: 'Actualizando inventario...',
                        allowOutsideClick: false,
                        didOpen: () => { Swal.showLoading(); }
                    });

                    // Construcción del payload
                    const formData = new FormData();
                    formData.append('action', 'abastecer_producto');
                    formData.append('id_inventario', idInventario);
                    formData.append('id_producto', idProducto);
                    formData.append('id_sucursal', idSucursal);
                    formData.append('cantidad', cantidadAnadir);

                    // Envío al mismo controlador de inventario
                    fetch('api.php?resource=inventario', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            Swal.fire({
                                title: '¡Abastecido!',
                                text: data.message,
                                icon: 'success',
                                confirmButtonColor: '#20c997'
                            }).then(() => {
                                location.reload(); // Recarga para ver reflejado el nuevo stock
                            });
                        } else {
                            Swal.fire({
                                title: 'Error',
                                text: data.message,
                                icon: 'error',
                                confirmButtonColor: '#6c757d'
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            title: 'Error de Conexión',
                            text: 'No se pudo procesar el reabastecimiento.',
                            icon: 'error',
                            confirmButtonColor: '#6c757d'
                        });
                    });
                }
            });
        });
    });
});
