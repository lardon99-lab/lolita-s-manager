document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('formAbastecerInsumos');
    if (!form) return;

    const keyInput = form.querySelector('[name="idempotency_key"]');
    const modal = form.closest('.modal');
    const quantityInputs = Array.from(form.querySelectorAll('[data-supply-id]'));
    const hint = form.querySelector('[data-supply-restock-hint]');
    const newKey = () => window.crypto?.randomUUID?.() || `supply-${Date.now()}-${Math.random().toString(16).slice(2)}`;
    keyInput.value = newKey();

    modal?.addEventListener('show.bs.modal', (event) => {
        quantityInputs.forEach((input) => {
            input.value = '0';
            input.closest('[data-supply-restock-row]')?.classList.remove('is-selected');
        });

        const selectedId = event.relatedTarget?.dataset.supplyId;
        const selectedInput = selectedId
            ? quantityInputs.find((input) => input.dataset.supplyId === selectedId)
            : null;

        if (!selectedInput) {
            if (hint) hint.textContent = 'Puedes registrar cantidades para varios tipos de vaso en una sola entrada.';
            return;
        }

        const row = selectedInput.closest('[data-supply-restock-row]');
        row?.classList.add('is-selected');
        const supplyName = row?.querySelector('strong')?.textContent?.trim() || 'este vaso';
        if (hint) hint.textContent = `Vaso seleccionado: ${supplyName}. Puedes indicar la cantidad aquí o agregar otros vasos a la misma entrada.`;
    });

    modal?.addEventListener('shown.bs.modal', (event) => {
        const selectedId = event.relatedTarget?.dataset.supplyId;
        if (!selectedId) return;

        const selectedInput = quantityInputs.find((input) => input.dataset.supplyId === selectedId);
        selectedInput?.focus({ preventScroll: true });
        selectedInput?.closest('[data-supply-restock-row]')?.scrollIntoView({ block: 'nearest' });
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const items = quantityInputs
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
