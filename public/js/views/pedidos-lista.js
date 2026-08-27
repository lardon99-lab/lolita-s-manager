function gestionarPedido(id, estadoActual, saldo, rol) {
    const saldoNum = parseFloat(saldo) || 0;
    let estadosDisponibles = [];
    let iconos = {
        'En Preparación': 'fa-fire-burner',
        'Listo': 'fa-check',
        'Entregado': 'fa-box-archive',
        'Cancelado': 'fa-xmark'
    };
    let colores = {
        'En Preparación': 'btn-info',
        'Listo': 'btn-success',
        'Entregado': 'btn-secondary',
        'Cancelado': 'btn-danger'
    };

    // CONFIGURACIÓN DE OPCIONES POR ROL
    if (rol === 1 || rol === 3) {
        estadosDisponibles = ['En Preparación', 'Listo', 'Entregado', 'Cancelado'];
    } else {
        estadosDisponibles = ['Entregado'];
    }

    // Filtrar estados diferentes al actual
    estadosDisponibles = estadosDisponibles.filter(est => est !== estadoActual);

    let html = '<div class="dialog-summary">';
    html += '<div><span class="dialog-summary__label">Estado actual</span>';
    html += '<span class="dialog-summary__value">' + estadoActual + '</span></div>';
    html += '<div><span class="dialog-summary__label">Saldo pendiente</span>';
    html += '<span class="dialog-summary__value">L. ' + saldoNum.toFixed(2) + '</span></div>';
    html += '</div><div class="dialog-action-list">';

    estadosDisponibles.forEach(estado => {
        html += '<button type="button" class="btn ' + colores[estado] + ' text-white" ';
        html += 'onclick="confirmarCambioEstado(' + id + ', \'' + estado + '\', ' + saldoNum + ', ' + rol + ')">';
        html += '<i class="fa-solid ' + iconos[estado] + ' me-2"></i> ' + estado;
        html += '</button>';
    });

    html += '</div>';

    Swal.fire({
        title: 'Cambiar Estado del Pedido',
        html: html,
        showConfirmButton: false,
        showCancelButton: true,
        cancelButtonColor: '#6c757d',
        cancelButtonText: 'Cerrar',
        customClass: { cancelButton: 'btn app-dialog__button app-dialog__button--ghost' }
    });
}

function confirmarCambioEstado(id, nuevoEstado, saldo, rol) {
    if ((nuevoEstado === 'Entregado' || (nuevoEstado === 'Listo' && rol !== 2)) && saldo > 0) {
        confirmarPagoPendiente(id, saldo, nuevoEstado);
    } else {
        procesarCambioEstado(id, nuevoEstado);
    }
}

function confirmarPagoPendiente(id, saldo, nuevoEstado) {
    Swal.fire({
        title: '¡Cobro Pendiente!',
        html: `Para poder entregar el pedido, el cliente debe liquidar el saldo restante de:<br><br><h2 class="text-danger fw-black mb-0">L. ${saldo.toFixed(2)}</h2><br>¿Confirmas que has recibido el pago completo?`,
        icon: 'warning',
        showCancelButton: true,
        showDenyButton: true,
        confirmButtonColor: '#198754',
        denyButtonColor: '#0dcaf0',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="fa-solid fa-check me-1"></i> Sí, ya pagó',
        denyButtonText: 'Cambiar sin cobrar',
        cancelButtonText: 'Cancelar',
        customClass: { confirmButton: 'rounded-pill', denyButton: 'rounded-pill text-white', cancelButton: 'rounded-pill' }
    }).then((result) => {
        if (result.isConfirmed) {
            procesarCambioEstado(id, nuevoEstado, true);
        } else if (result.isDenied) {
            procesarCambioEstado(id, nuevoEstado, false);
        }
    });
}

function procesarCambioEstado(id, estado, liquidarSaldo = false) {
    const url = `api.php?resource=pedidos&action=actualizar_estado_ajax&id=${id}&nuevo_estado=${encodeURIComponent(estado)}&liquidar=${liquidarSaldo}`;
    
    // Mostramos un loader mientras procesa
    Swal.fire({
        title: 'Actualizando...',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });

    fetch(url, { method: 'POST' })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: '¡Éxito!',
                    text: data.message,
                    confirmButtonColor: '#198754',
                    customClass: { confirmButton: 'rounded-pill px-4' }
                }).then(() => location.reload());
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        })
        .catch(() => Swal.fire('Error', 'Fallo en la comunicación con el servidor', 'error'));
}
