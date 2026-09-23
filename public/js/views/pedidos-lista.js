const orderStateStyles = {
    'Terminado': {
        icon: 'fa-check',
        modifier: 'order-state-action--finished',
        label: 'Marcar como terminado',
        description: 'Habilita la entrega para el empleado.',
    },
    'Entregado': {
        icon: 'fa-box-archive',
        modifier: 'order-state-action--delivered',
        label: 'Entregar y cobrar',
        description: 'Confirma la entrega y finaliza el pedido.',
    },
};

function createSummaryItem(label, value, modifier = '') {
    const item = document.createElement('div');
    item.className = 'dialog-summary__item';

    const labelElement = document.createElement('span');
    labelElement.className = 'dialog-summary__label';
    labelElement.textContent = label;

    const valueElement = document.createElement('strong');
    valueElement.className = `dialog-summary__value ${modifier}`.trim();
    valueElement.textContent = value;
    item.append(labelElement, valueElement);
    return item;
}

function gestionarPedido(id, estadoActual, saldo, transiciones) {
    const balance = Number.parseFloat(saldo) || 0;
    const actions = document.createElement('div');
    const availableTransitions = Array.isArray(transiciones) ? transiciones : [];
    actions.className = `dialog-action-list${availableTransitions.length === 1 ? ' dialog-action-list--single' : ''}`;

    availableTransitions.forEach((state) => {
        const style = orderStateStyles[state];
        if (!style) return;
        const button = document.createElement('button');
        button.type = 'button';
        button.className = `btn order-state-action ${style.modifier}`;

        const iconWrapper = document.createElement('span');
        iconWrapper.className = 'order-state-action__icon';
        const iconElement = document.createElement('i');
        iconElement.className = `fa-solid ${style.icon}`;
        iconElement.setAttribute('aria-hidden', 'true');
        iconWrapper.appendChild(iconElement);

        const copy = document.createElement('span');
        copy.className = 'order-state-action__copy';
        const label = document.createElement('strong');
        label.textContent = style.label;
        const description = document.createElement('small');
        description.textContent = state === 'Entregado' && balance > 0
            ? `Registra L. ${balance.toFixed(2)} y finaliza el pedido.`
            : style.description;
        copy.append(label, description);

        button.append(iconWrapper, copy);
        button.addEventListener('click', () => confirmarCambioEstado(id, state, balance));
        actions.appendChild(button);
    });

    const summary = document.createElement('div');
    summary.className = 'dialog-summary';
    summary.append(
        createSummaryItem('Estado actual', estadoActual),
        createSummaryItem(
            'Saldo pendiente',
            `L. ${balance.toFixed(2)}`,
            balance > 0 ? 'dialog-summary__value--pending' : 'dialog-summary__value--paid'
        )
    );
    const content = document.createElement('div');
    content.append(summary, actions);

    Swal.fire({
        title: 'Cambiar estado del pedido',
        html: content,
        showConfirmButton: false,
        showCancelButton: true,
        cancelButtonText: 'Cerrar',
        customClass: {
            popup: 'order-state-dialog',
            htmlContainer: 'order-state-dialog__body',
            actions: 'order-state-dialog__footer',
        },
    });
}

async function confirmarCambioEstado(id, nuevoEstado, saldo) {
    let liquidar = false;
    let metodoPago = 'Efectivo';

    if (nuevoEstado === 'Entregado' && saldo > 0) {
        const payment = await Swal.fire({
            title: 'Liquidar saldo pendiente',
            text: `Confirma la recepción de L. ${saldo.toFixed(2)} y selecciona el método de pago.`,
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
            text: `El pedido pasará a ${nuevoEstado}.`,
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
        .catch((error) => Swal.fire('Error', error.message || 'Fallo en la comunicación con el servidor.', 'error'));
}
