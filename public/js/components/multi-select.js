(function () {
    'use strict';

    const instances = new WeakMap();

    function init(select) {
        if (!(select instanceof HTMLSelectElement) || instances.has(select)) return;
        select.dataset.nativeSelect = 'true';
        select.classList.add('app-multi-select__native');

        const root = document.createElement('div');
        root.className = 'app-multi-select';
        const trigger = document.createElement('button');
        trigger.type = 'button';
        trigger.className = 'form-select app-multi-select__trigger';
        trigger.setAttribute('aria-haspopup', 'listbox');
        trigger.setAttribute('aria-expanded', 'false');
        const panel = document.createElement('div');
        panel.className = 'app-multi-select__panel';
        panel.hidden = true;
        panel.setAttribute('role', 'listbox');
        select.insertAdjacentElement('afterend', root);
        root.append(trigger, panel);

        function selectedOptions() {
            return Array.from(select.selectedOptions);
        }

        function updateLabel() {
            const selected = selectedOptions();
            trigger.textContent = selected.length === 0
                ? 'Selecciona una sucursal'
                : selected.length === 1 ? selected[0].textContent.trim() : `${selected.length} sucursales seleccionadas`;
            trigger.classList.toggle('is-placeholder', selected.length === 0);
        }

        function render() {
            panel.replaceChildren();
            panel.setAttribute('aria-multiselectable', select.multiple ? 'true' : 'false');
            Array.from(select.options).forEach((option) => {
                const label = document.createElement('label');
                label.className = 'app-multi-select__option';
                const input = document.createElement('input');
                input.type = select.multiple ? 'checkbox' : 'radio';
                input.name = `${select.id}-visual`;
                input.checked = option.selected;
                input.disabled = option.disabled;
                input.addEventListener('change', () => {
                    if (!select.multiple) Array.from(select.options).forEach((item) => { item.selected = false; });
                    option.selected = input.checked;
                    select.dispatchEvent(new Event('change', { bubbles: true }));
                    if (!select.multiple) close();
                    else render();
                });
                const text = document.createElement('span');
                text.textContent = option.textContent;
                label.append(input, text);
                panel.append(label);
            });
            updateLabel();
        }

        function close() {
            panel.hidden = true;
            trigger.setAttribute('aria-expanded', 'false');
        }

        trigger.addEventListener('click', () => {
            panel.hidden = !panel.hidden;
            trigger.setAttribute('aria-expanded', panel.hidden ? 'false' : 'true');
        });
        select.addEventListener('change', render);
        new MutationObserver(render).observe(select, { attributes: true, attributeFilter: ['multiple', 'disabled'] });
        document.addEventListener('click', (event) => { if (!root.contains(event.target)) close(); });
        root.addEventListener('keydown', (event) => { if (event.key === 'Escape') { close(); trigger.focus(); } });
        instances.set(select, { render, close });
        render();
    }

    function scan(root) {
        root.querySelectorAll?.('select[data-app-multiselect]').forEach(init);
    }

    function sync(select) {
        instances.get(select)?.render();
    }

    document.addEventListener('DOMContentLoaded', () => scan(document));
    window.AppMultiSelect = { scan, sync };
})();
