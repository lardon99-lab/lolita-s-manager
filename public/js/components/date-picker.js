(function () {
    'use strict';

    function init(input) {
        if (!(input instanceof HTMLInputElement) || input._flatpickr || typeof window.flatpickr !== 'function') return;
        window.flatpickr(input, {
            dateFormat: 'Y-m-d',
            altInput: true,
            altFormat: 'd/m/Y',
            altInputClass: `${input.className} app-date-input`,
            allowInput: false,
            disableMobile: true,
            minDate: input.min || null,
            maxDate: input.max || null,
            locale: window.flatpickr.l10ns.es,
            onReady(_dates, _value, instance) {
                instance.calendarContainer.classList.add('app-date-calendar');
                const month = instance.calendarContainer.querySelector('.flatpickr-monthDropdown-months');
                if (month) month.dataset.nativeSelect = 'true';
            },
        });
    }

    function scan(root) {
        if (root instanceof HTMLInputElement && root.matches('[data-app-date]')) init(root);
        root.querySelectorAll?.('[data-app-date]').forEach(init);
    }

    document.addEventListener('DOMContentLoaded', () => scan(document));
    document.addEventListener('change', (event) => {
        const input = event.target;
        if (!(input instanceof HTMLInputElement) || !input.matches('[data-app-date][data-date-peer]')) return;
        const peer = document.querySelector(input.dataset.datePeer);
        if (!(peer instanceof HTMLInputElement) || !peer._flatpickr) return;
        peer._flatpickr.set(input.dataset.dateBoundary === 'max' ? 'maxDate' : 'minDate', input.value || null);
    });
    new MutationObserver((records) => records.forEach((record) => record.addedNodes.forEach((node) => {
        if (node instanceof HTMLElement) scan(node);
    }))).observe(document.documentElement, { childList: true, subtree: true });
    window.AppDatePicker = { scan };
})();
