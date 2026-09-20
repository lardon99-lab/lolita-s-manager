(function () {
    'use strict';

    function create(root, options = {}) {
        const input = root?.querySelector('.js-client-combobox-input');
        const hidden = root?.querySelector('.js-client-combobox-value');
        const listbox = root?.querySelector('.js-client-combobox-list');
        const status = root?.querySelector('.js-client-combobox-status');
        if (!input || !hidden || !listbox) throw new Error('El selector de clientes esta incompleto.');

        let timer = null;
        let controller = null;
        let results = [];
        let activeIndex = -1;

        function open() {
            listbox.hidden = false;
            input.setAttribute('aria-expanded', 'true');
        }

        function close() {
            listbox.hidden = true;
            input.setAttribute('aria-expanded', 'false');
            input.removeAttribute('aria-activedescendant');
            activeIndex = -1;
        }

        function select(client) {
            hidden.value = String(client.id_cliente);
            input.value = client.nombre_completo;
            input.dataset.selectedLabel = client.nombre_completo;
            close();
            root.classList.add('has-selection');
            root.classList.remove('is-unresolved');
            if (status) status.textContent = `${client.nombre_completo} seleccionado.`;
            root.dispatchEvent(new CustomEvent('client-selected', { detail: client }));
        }

        function clearSelection() {
            hidden.value = '';
            delete input.dataset.selectedLabel;
            root.classList.remove('has-selection');
            root.classList.add('is-unresolved');
            if (status) status.textContent = 'Selecciona una coincidencia de la lista.';
        }

        function render(items) {
            results = items;
            activeIndex = -1;
            listbox.replaceChildren();
            if (!items.length) {
                const empty = document.createElement('div');
                empty.className = 'app-combobox__empty';
                empty.textContent = 'No se encontraron clientes.';
                listbox.appendChild(empty);
                if (status) status.textContent = 'No hay coincidencias. Puedes registrar un cliente nuevo.';
            } else {
                const normalized = normalize(input.value);
                const exact = items.find((client) => normalize(client.nombre_completo) === normalized || normalize(client.telefono || '') === normalized);
                if (exact && normalized !== '') {
                    select(exact);
                    return;
                }
                if (status) status.textContent = `${items.length} ${items.length === 1 ? 'coincidencia encontrada' : 'coincidencias encontradas'}.`;
                items.forEach((client, index) => {
                    const option = document.createElement('button');
                    option.type = 'button';
                    option.className = 'app-combobox__option';
                    option.id = `client-option-${client.id_cliente}`;
                    option.setAttribute('role', 'option');
                    option.setAttribute('aria-selected', 'false');
                    option.innerHTML = '<span class="app-combobox__avatar"><i class="fa-solid fa-user" aria-hidden="true"></i></span>';
                    const copy = document.createElement('span');
                    copy.className = 'app-combobox__option-copy';
                    const name = document.createElement('strong');
                    name.textContent = client.nombre_completo;
                    const phone = document.createElement('small');
                    phone.textContent = client.telefono || 'Sin telefono registrado';
                    copy.append(name, phone);
                    option.appendChild(copy);
                    option.addEventListener('pointerdown', (event) => event.preventDefault());
                    option.addEventListener('click', () => select(client));
                    option.dataset.index = String(index);
                    listbox.appendChild(option);
                });
            }
            const createButton = document.createElement('button');
            createButton.type = 'button';
            createButton.className = 'app-combobox__create';
            createButton.innerHTML = '<i class="fa-solid fa-user-plus" aria-hidden="true"></i><span>Registrar nuevo cliente</span>';
            createButton.addEventListener('click', () => options.onCreate?.(input.value.trim()));
            listbox.appendChild(createButton);
            open();
        }

        async function search() {
            controller?.abort();
            controller = new AbortController();
            listbox.innerHTML = '<div class="app-combobox__empty"><span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Buscando...</div>';
            open();
            try {
                const response = await fetch(`api.php?resource=clientes&action=buscar&q=${encodeURIComponent(input.value.trim())}`, {
                    headers: { Accept: 'application/json' },
                    signal: controller.signal,
                });
                const data = await response.json();
                if (!response.ok || data.status !== 'success') throw new Error(data.message || 'No fue posible buscar clientes.');
                render(data.clientes || []);
            } catch (error) {
                if (error.name === 'AbortError') return;
                listbox.innerHTML = '<div class="app-combobox__empty text-danger">No fue posible cargar los clientes.</div>';
            }
        }

        function moveActive(direction) {
            const optionsList = [...listbox.querySelectorAll('.app-combobox__option')];
            if (!optionsList.length) return;
            activeIndex = Math.max(0, Math.min(optionsList.length - 1, activeIndex + direction));
            optionsList.forEach((option, index) => option.classList.toggle('is-active', index === activeIndex));
            const active = optionsList[activeIndex];
            input.setAttribute('aria-activedescendant', active.id);
            active.scrollIntoView({ block: 'nearest' });
        }

        input.addEventListener('input', () => {
            if (input.value !== input.dataset.selectedLabel) clearSelection();
            listbox.innerHTML = '<div class="app-combobox__empty">Buscando coincidencias...</div>';
            open();
            window.clearTimeout(timer);
            timer = window.setTimeout(search, 220);
        });

        function normalize(value) {
            return String(value || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').trim().toLowerCase();
        }
        input.addEventListener('focus', search);
        input.addEventListener('keydown', (event) => {
            if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                event.preventDefault();
                if (listbox.hidden) search();
                moveActive(event.key === 'ArrowDown' ? 1 : -1);
            } else if (event.key === 'Enter' && activeIndex >= 0) {
                event.preventDefault();
                select(results[activeIndex]);
            } else if (event.key === 'Escape') {
                close();
            }
        });
        document.addEventListener('pointerdown', (event) => {
            if (!root.contains(event.target)) close();
        });

        return { select, search, clearSelection };
    }

    window.ClientCombobox = { create };
})();
