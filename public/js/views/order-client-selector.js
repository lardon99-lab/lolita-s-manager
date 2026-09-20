(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', () => {
        const root = document.getElementById('order-client-combobox');
        const modalElement = document.getElementById('modalCliente');
        const form = document.getElementById('formQuickClient');
        if (!root || !modalElement || !form) return;
        const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
        const error = form.querySelector('.js-client-form-error');
        const submit = form.querySelector('button[type="submit"]');

        const combobox = ClientCombobox.create(root, {
            onCreate(term) {
                form.reset();
                form.querySelector('[name="nombre"]').value = term;
                error.hidden = true;
                modal.show();
            },
        });

        document.getElementById('openClientModal')?.addEventListener('click', () => {
            const term = root.querySelector('.js-client-combobox-input').value.trim();
            form.reset();
            form.querySelector('[name="nombre"]').value = term;
            error.hidden = true;
        });

        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            error.hidden = true;
            submit.disabled = true;
            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: { Accept: 'application/json' },
                    body: new FormData(form),
                });
                const data = await response.json();
                if (!response.ok || data.status !== 'success') throw new Error(data.message || 'No fue posible registrar el cliente.');
                combobox.select(data.cliente);
                modal.hide();
                form.reset();
                document.querySelector('[name="fecha_entrega"]')?.focus();
            } catch (requestError) {
                error.textContent = requestError.message;
                error.hidden = false;
            } finally {
                submit.disabled = false;
            }
        });
    });
})();
