(() => {
    'use strict';

    function required(method) {
        return method === 'Tarjeta' || method === 'Transferencia';
    }

    function cents(value, allowCents) {
        const amount = value.trim().replace(',', '.');
        const pattern = allowCents ? /^[0-9]{1,8}(\.[0-9]{1,2})?$/ : /^[0-9]{1,8}$/;
        return pattern.test(amount) ? Math.round(Number(amount) * 100) : null;
    }

    function setAmountMode(fields, expected) {
        const amount = fields.querySelector('[data-receipt-amount]');
        const allowCents = Math.round(expected * 100) % 100 !== 0;
        amount.inputMode = allowCents ? 'decimal' : 'numeric';
        amount.pattern = allowCents ? '[0-9]{1,8}([.,][0-9]{1,2})?' : '[0-9]{1,8}';
        amount.maxLength = allowCents ? 11 : 8;
        amount.placeholder = allowCents ? '0.00' : '0';
        return amount;
    }

    function validate(fields, expected) {
        const amount = setAmountMode(fields, expected);
        const approved = cents(amount.value, amount.inputMode === 'decimal');
        amount.setCustomValidity(approved > 0 && approved === Math.round(expected * 100)
            ? ''
            : 'El importe del comprobante debe coincidir con el monto a registrar.');
        return amount;
    }

    async function confirm(method, expected, amountLabel, draft = {}) {
        const fields = document.createElement('div');
        fields.className = 'text-start';
        fields.append(document.getElementById('receipt-' + method).content.cloneNode(true));
        setAmountMode(fields, expected);
        const inputs = [...fields.querySelectorAll('input')];
        inputs.forEach((input) => {
            if (draft[input.name] !== undefined) input.value = draft[input.name];
        });
        const caption = document.createElement('p');
        caption.className = 'fw-bold text-dark mb-2';
        caption.textContent = `Monto a registrar: ${amountLabel}`;
        fields.prepend(caption);
        fields.addEventListener('input', (event) => event.target.setCustomValidity(''));

        const result = await Swal.fire({
            title: 'Registrar pago con ' + method.toLowerCase(),
            html: fields,
            showCancelButton: true,
            confirmButtonText: 'Registrar pago',
            cancelButtonText: 'Volver',
            focusConfirm: false,
            didOpen: () => fields.querySelector('input:not([type="hidden"])').focus(),
            preConfirm: () => {
                validate(fields, expected);
                if (inputs.some((input) => !input.reportValidity())) return false;
                return Object.fromEntries(inputs.map(({ name, value }) => [name, value]));
            },
        });
        return result.isConfirmed ? result.value : null;
    }

    async function send(url, options) {
        while (true) {
            const controller = new AbortController();
            const timeout = window.setTimeout(() => controller.abort(), 15000);
            try {
                const response = await fetch(url, { ...options, signal: controller.signal });
                if (response.status >= 500) throw new Error();
                return await response.json();
            } catch {
                // El servidor podría haber guardado el pago antes de perderse la respuesta.
            } finally {
                window.clearTimeout(timeout);
            }
            const result = await Swal.fire({
                title: 'Registro sin confirmar',
                text: 'Puedes reenviar los datos del comprobante o revisar el historial antes de intentar cobrar de nuevo.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Reintentar registro',
                cancelButtonText: 'Cerrar aviso',
            });
            if (!result.isConfirmed) return null;
        }
    }

    window.PaymentReceipt = { required, confirm, send, validate };
})();
