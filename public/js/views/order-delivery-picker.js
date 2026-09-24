(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', () => {
        const input = document.getElementById('order-delivery-at');
        if (!input || typeof window.flatpickr !== 'function') return;

        function preserveNativeCalendarControls(instance) {
            const monthSelect = instance.calendarContainer.querySelector('.flatpickr-monthDropdown-months');
            if (!(monthSelect instanceof HTMLSelectElement)) return;
            monthSelect.dataset.nativeSelect = 'true';
            window.AppSelect?.scan(instance.calendarContainer);
        }

        window.flatpickr(input, {
            enableTime: true,
            time_24hr: false,
            dateFormat: 'Y-m-d\\TH:i',
            altInput: true,
            altFormat: 'd/m/Y h:i K',
            altInputClass: 'form-control order-delivery-input',
            ariaDateFormat: 'l, j F Y',
            minDate: input.dataset.minDate || 'today',
            minuteIncrement: 5,
            defaultHour: 9,
            defaultMinute: 0,
            disableMobile: true,
            locale: window.flatpickr.l10ns.es,
            onReady(_dates, _value, instance) {
                instance.calendarContainer.classList.add('order-delivery-calendar');
                preserveNativeCalendarControls(instance);
                instance.altInput.required = true;
                instance.altInput.setAttribute('aria-label', 'Fecha y hora de entrega');
                instance.altInput.dataset.timezone = input.dataset.timezone || 'America/Tegucigalpa';
            },
            onMonthChange(_dates, _value, instance) {
                preserveNativeCalendarControls(instance);
            },
            onYearChange(_dates, _value, instance) {
                preserveNativeCalendarControls(instance);
            },
        });
    });
})();
