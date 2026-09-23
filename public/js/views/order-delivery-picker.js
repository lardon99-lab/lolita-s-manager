(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', () => {
        const input = document.getElementById('order-delivery-at');
        if (!input || typeof window.flatpickr !== 'function') return;

        window.flatpickr(input, {
            enableTime: true,
            time_24hr: false,
            dateFormat: 'Y-m-d\\TH:i',
            altInput: true,
            altFormat: 'd/m/Y h:i K',
            altInputClass: 'form-control order-delivery-input',
            ariaDateFormat: 'l, j F Y',
            minDate: new Date(),
            minuteIncrement: 5,
            defaultHour: 9,
            defaultMinute: 0,
            disableMobile: true,
            locale: window.flatpickr.l10ns.es,
            onReady(_dates, _value, instance) {
                instance.altInput.required = true;
                instance.altInput.setAttribute('aria-label', 'Fecha y hora de entrega');
            },
        });
    });
})();
