(function () {
    'use strict';

    function normalize(value) {
        return String(value || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim();
    }

    function filter(input) {
        const selector = input.dataset.tableSearch || input.dataset.listSearch || '';
        const target = document.querySelector(selector);
        if (!target) return;
        const query = normalize(input.value);
        let visible = 0;
        const items = input.dataset.listSearch
            ? target.querySelectorAll('[data-search-item]')
            : target.querySelectorAll('tbody tr');
        items.forEach((row) => {
            const matches = !query || normalize(row.dataset.searchText || row.textContent).includes(query);
            row.hidden = !matches;
            if (matches && row.getClientRects().length > 0) visible += 1;
        });
        const status = document.querySelector(input.dataset.searchStatus || '');
        if (status) status.textContent = `${visible} resultado${visible === 1 ? '' : 's'}`;
    }

    document.addEventListener('input', (event) => {
        if (event.target instanceof HTMLInputElement && event.target.matches('[data-table-search], [data-list-search]')) filter(event.target);
    });
    document.querySelectorAll('[data-table-search], [data-list-search]').forEach(filter);
})();
