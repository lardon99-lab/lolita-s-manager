(function () {
    'use strict';

    function create(container) {
        if (!container) throw new Error('Falta el contenedor de personalizaciones.');

        function addGroup(data = {}) {
            const group = document.createElement('section');
            group.className = 'customization-group';
            group.innerHTML = `
                <div class="customization-group__header">
                    <div><label class="form-label small fw-semibold">Grupo</label><input class="form-control js-group-name" maxlength="50" placeholder="Ej. Relleno" required></div>
                    <div class="form-check mb-2"><input class="form-check-input js-group-required" type="checkbox"><label class="form-check-label">Obligatorio</label></div>
                    <div><label class="form-label small fw-semibold">Maximo</label><input class="form-control js-group-max" type="number" min="1" max="25" value="1" required></div>
                    <button class="btn btn-outline-danger customization-remove js-remove-group" type="button" title="Eliminar grupo" aria-label="Eliminar grupo"><i class="fa-solid fa-trash-can"></i></button>
                </div>
                <div class="customization-options"></div>
                <button class="btn btn-sm btn-outline-secondary mt-3 js-add-option" type="button"><i class="fa-solid fa-plus me-1"></i>Opcion</button>`;
            group.querySelector('.js-group-name').value = data.nombre || '';
            group.querySelector('.js-group-required').checked = Number(data.minimo_selecciones || 0) > 0 || Boolean(data.obligatorio);
            group.querySelector('.js-group-max').value = data.maximo_selecciones || data.maximo || 1;
            group.querySelector('.js-remove-group').addEventListener('click', () => group.remove());
            const options = group.querySelector('.customization-options');
            group.querySelector('.js-add-option').addEventListener('click', () => addOption(options));
            (data.opciones || []).forEach((option) => addOption(options, option));
            if (!data.opciones?.length) addOption(options);
            container.appendChild(group);
        }

        function addOption(options, data = {}) {
            const option = document.createElement('div');
            option.className = 'customization-option';
            option.innerHTML = `
                <input class="form-control js-option-name" maxlength="80" placeholder="Nombre de la opcion" aria-label="Nombre de la opcion" required>
                <div class="input-group"><span class="input-group-text">L.</span><input class="form-control js-option-price" type="number" min="0" max="1000000" step="0.01" value="0" aria-label="Recargo" required></div>
                <div class="form-check"><input class="form-check-input js-option-default" type="checkbox"><label class="form-check-label small">Predeterminada</label></div>
                <button class="btn btn-outline-danger customization-remove" type="button" title="Eliminar opcion" aria-label="Eliminar opcion"><i class="fa-solid fa-xmark"></i></button>`;
            option.querySelector('.js-option-name').value = data.nombre || '';
            option.querySelector('.js-option-price').value = Number(data.recargo || 0).toFixed(2);
            option.querySelector('.js-option-default').checked = Boolean(Number(data.predeterminada || 0));
            option.querySelector('button').addEventListener('click', () => option.remove());
            options.appendChild(option);
        }

        function getGroups() {
            return [...container.querySelectorAll('.customization-group')].map((group) => ({
                nombre: group.querySelector('.js-group-name').value,
                obligatorio: group.querySelector('.js-group-required').checked,
                maximo: group.querySelector('.js-group-max').value,
                opciones: [...group.querySelectorAll('.customization-option')].map((option) => ({
                    nombre: option.querySelector('.js-option-name').value,
                    recargo: option.querySelector('.js-option-price').value,
                    predeterminada: option.querySelector('.js-option-default').checked,
                })),
            }));
        }

        function setGroups(groups = []) {
            container.replaceChildren();
            groups.forEach((group) => addGroup(group));
        }

        function setLoading() {
            const state = document.createElement('div');
            state.className = 'py-5 text-center text-muted';
            const spinner = document.createElement('span');
            spinner.className = 'spinner-border spinner-border-sm me-2';
            spinner.setAttribute('aria-hidden', 'true');
            state.append(spinner, document.createTextNode('Cargando...'));
            container.replaceChildren(state);
        }

        return { addGroup, getGroups, setGroups, setLoading };
    }

    window.ProductCustomizationEditor = { create };
})();
