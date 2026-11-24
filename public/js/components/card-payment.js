(() => {
    'use strict';

    function cents(value) {
        const amount = value.trim().replace(',', '.');
        return /^[0-9]{1,8}(\.[0-9]{1,2})?$/.test(amount) ? Math.round(Number(amount) * 100) : null;
    }

    function validate(fields, expected) {
        const amount = fields.querySelector('[name="monto_tarjeta"]');
        const approved = cents(amount.value);
        amount.setCustomValidity(approved > 0 && approved === Math.round(expected * 100)
            ? ''
            : 'El importe aprobado debe coincidir con el monto a registrar.');
        return amount;
    }

    async function confirm(expected, amountLabel, draft = {}) {
        const fields = document.createElement('div');
        fields.className = 'text-start';
        fields.append(document.getElementById('cardPaymentFields').content.cloneNode(true));
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
            title: 'Registrar pago con tarjeta',
            html: fields,
            showCancelButton: true,
            confirmButtonText: 'Registrar pago aprobado',
            cancelButtonText: 'Volver',
            focusConfirm: false,
            didOpen: () => fields.querySelector('[name="terminal_tarjeta"]').focus(),
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

    window.CardPayment = { confirm, send, validate };
})();
