(function () {
    'use strict';

    const GROUPS = [
        { code: 'cake_flavor', name: 'Sabor de torta', icon: 'fa-cookie-bite', placeholder: 'Ej. Vainilla', defaultOption: 'Vainilla' },
        { code: 'cake_filling', name: 'Relleno', icon: 'fa-layer-group', placeholder: 'Ej. Jalea de piña', defaultOption: 'Jalea de piña' },
        { code: 'cake_covering', name: 'Cubierta', icon: 'fa-ice-cream', placeholder: 'Ej. Betún', defaultOption: 'Betún' },
    ];

    function create(container) {
        if (!container) throw new Error('Falta el editor de opciones del pastel.');

        function render(groups = []) {
            container.replaceChildren();
            GROUPS.forEach((definition, index) => {
                const saved = groups.find((group) => group.codigo === definition.code) || {};
                container.appendChild(createGroup(definition, saved, index === 0));
            });
        }

        function createGroup(definition, data, expanded) {
            const section = document.createElement('section');
            section.className = 'cake-option-group';
            section.dataset.code = definition.code;
            section.dataset.name = definition.name;
            section.innerHTML = `
                <button class="cake-option-group__toggle" type="button" aria-expanded="${expanded}">
                    <span class="cake-option-group__identity">
                        <span class="cake-option-group__icon"><i class="fa-solid ${definition.icon}" aria-hidden="true"></i></span>
                        <span><strong>${definition.name}</strong><small>El cliente elegira una opcion</small></span>
                    </span>
                    <span class="cake-option-group__summary"><span class="js-option-count">0</span><i class="fa-solid fa-chevron-down" aria-hidden="true"></i></span>
                </button>
                <div class="cake-option-group__body" ${expanded ? '' : 'hidden'}>
                    <div class="cake-option-list"></div>
                    <button class="btn btn-outline-primary cake-add-option js-add-option" type="button">
                        <i class="fa-solid fa-plus" aria-hidden="true"></i><span>Agregar opcion</span>
                    </button>
                </div>`;
            const toggle = section.querySelector('.cake-option-group__toggle');
            const body = section.querySelector('.cake-option-group__body');
            toggle.addEventListener('click', () => {
                const open = toggle.getAttribute('aria-expanded') === 'true';
                toggle.setAttribute('aria-expanded', String(!open));
                body.hidden = open;
            });
            section.querySelector('.js-add-option').addEventListener('click', () => addOption(section, {}, true));
            (data.opciones || []).forEach((option) => addOption(section, option));
            if (!data.opciones?.length) addOption(section, { nombre: definition.defaultOption, predeterminada: 1 });
            return section;
        }

        function addOption(section, data = {}, focus = false) {
            const list = section.querySelector('.cake-option-list');
            const option = document.createElement('div');
            option.className = 'cake-option-row';
            const defaultName = `cake-default-${section.dataset.code}`;
            option.innerHTML = `
                <label class="cake-option-row__name"><span>Opcion</span><input class="form-control js-option-name" maxlength="80" required></label>
                <label class="cake-option-row__price"><span>Recargo</span><span class="input-group"><span class="input-group-text">L.</span><input class="form-control js-option-price" type="number" min="0" max="1000000" step="0.01" value="0" required></span></label>
                <label class="cake-option-row__default"><input class="form-check-input js-option-default" name="${defaultName}" type="radio"><span>Predeterminada</span></label>
                <button class="btn btn-outline-danger cake-option-row__remove" type="button" title="Eliminar opcion" aria-label="Eliminar opcion"><i class="fa-solid fa-trash-can" aria-hidden="true"></i></button>`;
            option.querySelector('.js-option-name').value = data.nombre || '';
            const definition = GROUPS.find((group) => group.code === section.dataset.code);
            option.querySelector('.js-option-name').placeholder = definition?.placeholder || 'Nombre de la opcion';
            option.querySelector('.js-option-price').value = Number(data.recargo || 0).toFixed(2);
            option.querySelector('.js-option-default').checked = Boolean(Number(data.predeterminada || 0));
            option.querySelector('.cake-option-row__remove').addEventListener('click', () => {
                if (list.children.length === 1) {
                    option.querySelector('.js-option-name').value = '';
                    option.querySelector('.js-option-price').value = '0.00';
                    option.querySelector('.js-option-default').checked = false;
                    option.querySelector('.js-option-name').focus();
                    return;
                }
                option.remove();
                updateCount(section);
            });
            list.appendChild(option);
            updateCount(section);
            if (focus) option.querySelector('.js-option-name').focus({ preventScroll: true });
        }

        function updateCount(section) {
            const count = section.querySelectorAll('.cake-option-row').length;
            section.querySelector('.js-option-count').textContent = `${count} ${count === 1 ? 'opcion' : 'opciones'}`;
        }

        function getGroups() {
            return [...container.querySelectorAll('.cake-option-group')].map((section) => ({
                codigo: section.dataset.code,
                nombre: section.dataset.name,
                obligatorio: true,
                maximo: 1,
                opciones: [...section.querySelectorAll('.cake-option-row')].map((option) => ({
                    nombre: option.querySelector('.js-option-name').value.trim(),
                    recargo: option.querySelector('.js-option-price').value,
                    predeterminada: option.querySelector('.js-option-default').checked,
                })),
            }));
        }

        render();
        return { getGroups, setGroups: render };
    }

    window.CakeConfigurationEditor = { create };
})();
