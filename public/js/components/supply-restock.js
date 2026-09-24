document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('formAbastecerInsumos');
    if (!form) return;

    const keyInput = form.querySelector('[name="idempotency_key"]');
    const newKey = () => window.crypto?.randomUUID?.() || `supply-${Date.now()}-${Math.random().toString(16).slice(2)}`;
    keyInput.value = newKey();

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const items = Array.from(form.querySelectorAll('[data-supply-id]'))
            .map((input) => ({ supply_id: Number(input.dataset.supplyId), quantity: Number(input.value) }))
            .filter((item) => Number.isFinite(item.quantity) && item.quantity > 0);
        if (!items.length) {
            Swal.fire('Sin cantidades', 'Ingresa al menos una cantidad para abastecer.', 'warning');
            return;
        }

        const submit = form.querySelector('[type="submit"]');
        submit.disabled = true;
        const data = new FormData(form);
        data.set('items', JSON.stringify(items));
        try {
            const response = await fetch('api.php?resource=inventario&action=abastecer_insumos', { method: 'POST', body: data });
            const result = await response.json();
            if (!response.ok || result.status !== 'success') throw new Error(result.message || 'No fue posible registrar la entrada.');
            keyInput.value = newKey();
            await Swal.fire('Entrada registrada', result.message, 'success');
            location.reload();
        } catch (error) {
            Swal.fire('Error', error.message || 'No fue posible conectar con el servidor.', 'error');
            submit.disabled = false;
        }
    });
});
