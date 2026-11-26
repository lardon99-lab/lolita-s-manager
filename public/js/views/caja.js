(() => {
    const form = document.querySelector('[data-cash-expected]');
    if (!form) return;

    const counted = form.elements.namedItem('contado');
    const note = form.elements.namedItem('nota');
    const summary = form.querySelector('[data-cash-difference]');
    const expected = Number(form.dataset.cashExpected);

    function cents(value) {
        const pattern = counted.inputMode === 'decimal' ? /^(\d{1,10})(?:[.,](\d{1,2}))?$/ : /^(\d{1,10})$/;
        const parts = value.trim().match(pattern);
        return parts ? Number(parts[1]) * 100 + Number((parts[2] || '').padEnd(2, '0')) : null;
    }

    function updateSummary() {
        const amount = cents(counted.value);
        if (amount === null) {
            summary.textContent = '';
            note.required = false;
            return;
        }
        const difference = amount - expected;
        const money = 'L. ' + window.AppMoney(Math.abs(difference) / 100);
        note.required = difference !== 0;
        if (difference > 0) summary.textContent = 'Sobrante de ' + money + '.';
        else if (difference < 0) summary.textContent = 'Faltante de ' + money + '.';
        else summary.textContent = 'El efectivo contado coincide con el esperado.';
    }

    form.addEventListener('input', updateSummary);
    updateSummary();
})();
