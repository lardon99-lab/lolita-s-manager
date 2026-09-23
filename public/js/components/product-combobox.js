(function () {
    'use strict';

    function create(select) {
        if (!(select instanceof HTMLSelectElement)) throw new Error('El selector de productos no es valido.');

        const root = document.createElement('div');
        root.className = 'app-combobox product-combobox';
        const control = document.createElement('div');
        control.className = 'app-combobox__control';
        control.innerHTML = '<i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>';
        const input = document.createElement('input');
        input.type = 'text';
        input.className = 'app-combobox__input';
        input.placeholder = 'Buscar producto por nombre o categoria...';
        input.autocomplete = 'off';
        input.setAttribute('role', 'combobox');
        input.setAttribute('aria-autocomplete', 'list');
        input.setAttribute('aria-expanded', 'false');
        const list = document.createElement('div');
        list.className = 'app-combobox__list';
        list.id = `${select.id || 'product'}-search-results`;
        list.setAttribute('role', 'listbox');
        list.hidden = true;
        input.setAttribute('aria-controls', list.id);
        const status = document.createElement('div');
        status.className = 'app-combobox__status';
        status.setAttribute('aria-live', 'polite');
        status.textContent = 'Escribe para buscar un producto disponible.';
        control.appendChild(input);
        root.append(control, list, status);
        select.insertAdjacentElement('afterend', root);
        select.classList.add('product-combobox__native');

        let visibleOptions = [];
        let activeIndex = -1;

        function normalize(value) {
            return String(value || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim();
        }

        function candidates() {
            const term = normalize(input.value);
            return [...select.options].filter((option) => {
                if (!option.value || option.hidden || option.disabled) return false;
                const haystack = normalize(`${option.dataset.nombre || option.textContent} ${option.dataset.categoria || ''}`);
                return term === '' || haystack.includes(term);
            });
        }

        function open() {
            list.hidden = false;
            input.setAttribute('aria-expanded', 'true');
        }

        function close() {
            list.hidden = true;
            input.setAttribute('aria-expanded', 'false');
            input.removeAttribute('aria-activedescendant');
            activeIndex = -1;
        }

        function selectOption(option) {
            select.value = option.value;
            input.value = option.dataset.nombre || option.textContent.split('(')[0].trim();
            input.dataset.selectedValue = option.value;
            input.dataset.selectedLabel = input.value;
            status.textContent = `${input.value} seleccionado.`;
            root.classList.remove('is-unresolved');
            select.dispatchEvent(new Event('change', { bubbles: true }));
            close();
        }

        function render() {
            visibleOptions = candidates();
            activeIndex = -1;
            list.replaceChildren();
            if (!visibleOptions.length) {
                const empty = document.createElement('div');
                empty.className = 'app-combobox__empty';
                empty.textContent = 'No hay productos que coincidan en esta sucursal.';
                list.appendChild(empty);
                status.textContent = 'No se encontraron coincidencias.';
            } else {
                visibleOptions.forEach((option, index) => {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'app-combobox__option';
                    button.id = `${list.id}-option-${option.value}`;
                    button.setAttribute('role', 'option');
                    button.setAttribute('aria-selected', option.value === select.value ? 'true' : 'false');
                    button.innerHTML = '<span class="app-combobox__avatar"><i class="fa-solid fa-cake-candles" aria-hidden="true"></i></span>';
                    const copy = document.createElement('span');
                    copy.className = 'app-combobox__option-copy';
                    const name = document.createElement('strong');
                    name.textContent = option.dataset.nombre || option.textContent;
                    const details = document.createElement('small');
                    details.textContent = `${option.dataset.categoria || 'Sin categoria'} | L. ${Number(option.dataset.precio || 0).toFixed(2)}`;
                    copy.append(name, details);
                    button.appendChild(copy);
                    button.addEventListener('pointerdown', (event) => event.preventDefault());
                    button.addEventListener('click', () => selectOption(option));
                    button.dataset.index = String(index);
                    list.appendChild(button);
                });
                status.textContent = `${visibleOptions.length} ${visibleOptions.length === 1 ? 'producto encontrado' : 'productos encontrados'}.`;
            }
            open();
        }

        function clear() {
            select.value = '';
            input.value = '';
            delete input.dataset.selectedValue;
            delete input.dataset.selectedLabel;
            status.textContent = 'Escribe para buscar un producto disponible.';
            root.classList.remove('is-unresolved');
            close();
        }

        function sync() {
            const selected = select.selectedOptions[0];
            if (selected?.value) {
                input.value = selected.dataset.nombre || selected.textContent.split('(')[0].trim();
                input.dataset.selectedValue = selected.value;
                input.dataset.selectedLabel = input.value;
            } else {
                clear();
            }
        }

        input.addEventListener('input', () => {
            if (input.value !== input.dataset.selectedLabel) {
                select.value = '';
                delete input.dataset.selectedValue;
                root.classList.add('is-unresolved');
            }
            render();
        });
        input.addEventListener('focus', render);
        input.addEventListener('keydown', (event) => {
            if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                event.preventDefault();
                if (list.hidden) render();
                activeIndex = Math.max(0, Math.min(visibleOptions.length - 1, activeIndex + (event.key === 'ArrowDown' ? 1 : -1)));
                list.querySelectorAll('.app-combobox__option').forEach((item, index) => item.classList.toggle('is-active', index === activeIndex));
                const active = list.querySelector(`[data-index="${activeIndex}"]`);
                if (active) {
                    input.setAttribute('aria-activedescendant', active.id);
                    active.scrollIntoView({ block: 'nearest' });
                }
            } else if (event.key === 'Enter' && activeIndex >= 0) {
                event.preventDefault();
                selectOption(visibleOptions[activeIndex]);
            } else if (event.key === 'Escape') {
                close();
            }
        });
        document.addEventListener('pointerdown', (event) => {
            if (!root.contains(event.target)) close();
        });

        return { clear, render, sync };
    }

    window.ProductCombobox = { create };
})();
