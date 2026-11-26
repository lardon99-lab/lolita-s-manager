(() => {
    const form = document.querySelector('[data-cash-count]');
    if (!form) return;

    const counted = form.elements.namedItem('contado');
    const envelopes = [...form.querySelectorAll('[data-cash-envelope]')];
    const summary = form.querySelector('[data-cash-distribution]');
    const fund = Number(form.dataset.cashCount);

    function cents(value) {
        const pattern = counted.inputMode === 'decimal' ? /^(\d{1,10})(?:[.,](\d{1,2}))?$/ : /^(\d{1,10})$/;
        const parts = value.trim().match(pattern);
        return parts ? Number(parts[1]) * 100 + Number((parts[2] || '').padEnd(2, '0')) : null;
    }

    function updateSummary() {
        const available = cents(counted.value);
        const amounts = envelopes.map((field) => cents(field.value));
        if (available === null || amounts.includes(null)) {
            summary.textContent = 'Ingresa el efectivo contado y los importes de los sobres para ver cuánto queda por repartir.';
            return;
        }
        if (available < fund) {
            summary.textContent = 'El efectivo contado queda por debajo del fondo fijo.';
            return;
        }
        const remaining = available - fund - amounts.reduce((sum, amount) => sum + amount, 0);
        const money = 'L. ' + window.AppMoney(Math.abs(remaining) / 100);
        if (remaining > 0) summary.textContent = 'Falta asignar ' + money + ' a los sobres.';
        else if (remaining < 0) summary.textContent = 'Los sobres superan el efectivo disponible por ' + money + '.';
        else summary.textContent = 'Los sobres coinciden con el efectivo disponible para retirar.';
    }

    form.addEventListener('input', updateSummary);
    updateSummary();
})();
