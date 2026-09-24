document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('branchForm');
    const modalElement = document.getElementById('branchModal');
    const modal = bootstrap.Modal.getOrCreateInstance(modalElement);

    modalElement.addEventListener('hidden.bs.modal', () => form.reset());
    document.querySelectorAll('.js-edit-branch').forEach((button) => {
        button.addEventListener('click', () => {
            document.getElementById('branchId').value = button.dataset.id;
            document.getElementById('branchName').value = button.dataset.name;
            document.getElementById('branchAddress').value = button.dataset.address;
            document.getElementById('branchPhone').value = button.dataset.phone;
            modal.show();
        });
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const action = document.getElementById('branchId').value ? 'actualizar' : 'guardar';
        await submitBranchForm(`api.php?resource=sucursales&action=${action}`, new FormData(form), form);
    });

    document.querySelectorAll('.js-state-branch').forEach((button) => {
        button.addEventListener('click', async () => {
            const body = new FormData();
            body.set('id_sucursal', button.dataset.id);
            body.set('estado', button.dataset.state);
            await submitBranchForm('api.php?resource=sucursales&action=cambiar_estado', body);
        });
    });
});

async function submitBranchForm(url, body, form = null) {
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
