const orderTransitions = {
    'Pendiente': ['En PreparaciÃ³n', 'Cancelado'],
    'En PreparaciÃ³n': ['Listo', 'Cancelado'],
    'Listo': ['Entregado', 'Cancelado'],
};

const orderStateStyles = {
    'En PreparaciÃ³n': ['fa-fire-burner', 'btn-info'],
    'Listo': ['fa-check', 'btn-success'],
    'Entregado': ['fa-box-archive', 'btn-secondary'],
    'Cancelado': ['fa-xmark', 'btn-danger'],
};

function gestionarPedido(id, estadoActual, saldo) {
    const balance = Number.parseFloat(saldo) || 0;
    const actions = document.createElement('div');
    actions.className = 'dialog-action-list';

    (orderTransitions[estadoActual] || []).forEach((state) => {
        const [icon, buttonClass] = orderStateStyles[state];
        const button = document.createElement('button');
        button.type = 'button';
        button.className = `btn ${buttonClass} text-white`;
        const iconElement = document.createElement('i');
        iconElement.className = `fa-solid ${icon} me-2`;
        button.append(iconElement, document.createTextNode(state));
        button.addEventListener('click', () => confirmarCambioEstado(id, state, balance));
        actions.appendChild(button);
    });

    const summary = document.createElement('div');
    summary.className = 'dialog-summary';
    summary.textContent = `Estado actual: ${estadoActual} | Saldo pendiente: L. ${balance.toFixed(2)}`;
    const content = document.createElement('div');
    content.append(summary, actions);

    Swal.fire({
        title: 'Cambiar estado del pedido',
        html: content,
        showConfirmButton: false,
        showCancelButton: true,
        cancelButtonText: 'Cerrar',
        customClass: { cancelButton: 'btn app-dialog__button app-dialog__button--ghost' },
    });
}

async function confirmarCambioEstado(id, nuevoEstado, saldo) {
    let liquidar = false;
    let metodoPago = 'Efectivo';

    if (nuevoEstado === 'Entregado' && saldo > 0) {
        const payment = await Swal.fire({
            title: 'Liquidar saldo pendiente',
            text: `Confirma la recepciÃ³n de L. ${saldo.toFixed(2)} y selecciona el mÃ©todo de pago.`,
            icon: 'warning',
            input: 'select',
            inputOptions: {
                Efectivo: 'Efectivo',
                Transferencia: 'Transferencia',
                Tarjeta: 'Tarjeta',
                Otro: 'Otro',
            },
            showCancelButton: true,
            confirmButtonText: 'Registrar pago y entregar',
            cancelButtonText: 'Cancelar',
        });
        if (!payment.isConfirmed) return;
        liquidar = true;
        metodoPago = payment.value;
    } else {
        const confirmation = await Swal.fire({
            title: 'Confirmar cambio',
            text: `El pedido pasarÃ¡ a ${nuevoEstado}.`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Confirmar',
            cancelButtonText: 'Cancelar',
        });
        if (!confirmation.isConfirmed) return;
    }

    procesarCambioEstado(id, nuevoEstado, liquidar, metodoPago);
}

function procesarCambioEstado(id, estado, liquidarSaldo = false, metodoPago = 'Efectivo') {
    Swal.fire({ title: 'Actualizando...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
    const body = new URLSearchParams({
        id: String(id),
        nuevo_estado: estado,
        liquidar: String(liquidarSaldo),
        metodo_pago: metodoPago,
    });

    fetch('api.php?resource=pedidos&action=actualizar_estado_ajax', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json' },
        body,
    })
        .then((response) => response.json())
        .then((data) => {
            if (data.status !== 'success') throw new Error(data.message || 'No fue posible actualizar el pedido.');
            return Swal.fire({ icon: 'success', title: 'Estado actualizado', text: data.message });
        })
        .then(() => location.reload())
        .catch((error) => Swal.fire('Error', error.message || 'Fallo en la comunicaciÃ³n con el servidor.', 'error'));
}
