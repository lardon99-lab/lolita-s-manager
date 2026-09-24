document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('clientForm');
    const modalElement = document.getElementById('clientModal');
    const modal = bootstrap.Modal.getOrCreateInstance(modalElement);

    modalElement.addEventListener('hidden.bs.modal', () => form.reset());
    document.querySelectorAll('.js-edit-client').forEach((button) => {
        button.addEventListener('click', () => {
            document.getElementById('clientId').value = button.dataset.id;
            document.getElementById('clientName').value = button.dataset.name;
            document.getElementById('clientPhone').value = button.dataset.phone;
            document.getElementById('clientEmail').value = button.dataset.email;
            modal.show();
        });
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const action = document.getElementById('clientId').value ? 'actualizar' : 'guardar';
        await submitManagementForm(`api.php?resource=clientes&action=${action}`, new FormData(form), form);
    });

    document.querySelectorAll('.js-state-client').forEach((button) => {
        button.addEventListener('click', async () => {
            const body = new FormData();
            body.set('id_cliente', button.dataset.id);
            body.set('estado', button.dataset.state);
            await submitManagementForm('api.php?resource=clientes&action=cambiar_estado', body);
        });
    });
});

async function submitManagementForm(url, body, form = null) {
    try {
        if (form) window.AppFormValidation?.setSubmitting(form, true);
        const response = await fetch(url, { method: 'POST', headers: { Accept: 'application/json' }, body });
        const data = await response.json();
        if (!response.ok || data.status !== 'success') {
            if (form && data.errors) window.AppFormValidation?.applyErrors(form, data.errors);
            throw new Error(data.message || 'No fue posible guardar los cambios.');
        }
        await Swal.fire('Cambios guardados', data.message, 'success');
        location.reload();
    } catch (error) {
        Swal.fire('Error', error.message || 'No fue posible completar la operacion.', 'error');
    } finally {
        if (form) window.AppFormValidation?.setSubmitting(form, false);
    }
}
