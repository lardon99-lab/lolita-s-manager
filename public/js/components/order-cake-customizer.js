(function () {
    'use strict';

    const money = (value) => Number(value || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    function create(panel, container, configurations, designPolicies) {
        function render(productId, productType = '') {
            productId = String(productId || '');
            const groups = configurations[productId] || [];
            const policy = designPolicies[productId] || {};
            container.replaceChildren();
            panel.classList.toggle('d-none', groups.length === 0 && !policy.permite_diseno && productType !== 'pastel');
            groups.forEach((group, index) => container.appendChild(createGroup(group, index)));
            if (productType === 'pastel') container.appendChild(createCakeMessage());
            if (policy.permite_diseno) container.appendChild(createDesign(policy));
            updateSurcharge();
        }

        function createCakeMessage() {
            const section = document.createElement('section');
            section.className = 'order-cake-message';
            section.innerHTML = `
                <label for="currentCakeMessage"><i class="fa-solid fa-quote-left" aria-hidden="true"></i>Frase para el pastel</label>
                <input id="currentCakeMessage" class="form-control js-cake-message" maxlength="250" placeholder="Ej. Feliz cumpleaños Pepe">
                <small>Opcional. Se enviara exactamente como se escriba.</small>`;
            return section;
        }

        function createGroup(group, index) {
            const fieldset = document.createElement('fieldset');
            fieldset.className = 'order-cake-group';
            fieldset.dataset.minimum = group.minimo_selecciones;
            fieldset.dataset.maximum = group.maximo_selecciones;
            const legend = document.createElement('legend');
            const step = document.createElement('span');
            step.textContent = index + 1;
            legend.append(step, document.createTextNode(`${group.nombre}${Number(group.minimo_selecciones) ? ' *' : ''}`));
            fieldset.appendChild(legend);
            const options = document.createElement('div');
            options.className = 'order-cake-options';
            (group.opciones || []).forEach((option) => {
                const label = document.createElement('label');
                label.className = 'order-cake-option';
                const input = document.createElement('input');
                input.className = 'form-check-input';
                input.type = Number(group.maximo_selecciones) === 1 ? 'radio' : 'checkbox';
                input.name = `draft-group-${group.id_grupo}`;
                input.value = option.id_opcion;
                input.dataset.surcharge = option.recargo;
                input.dataset.optionName = option.nombre;
                input.dataset.groupName = group.nombre;
                input.checked = Boolean(Number(option.predeterminada));
                input.addEventListener('change', () => {
                    if (input.type === 'checkbox') enforceMaximum(options, Number(group.maximo_selecciones));
                    updateSurcharge();
                });
                const copy = document.createElement('span');
                const name = document.createElement('strong');
                name.textContent = option.nombre;
                const surcharge = document.createElement('small');
                surcharge.textContent = Number(option.recargo) > 0 ? `+ L. ${money(option.recargo)}` : 'Sin recargo';
                copy.append(name, surcharge);
                label.append(input, copy);
                options.appendChild(label);
            });
            fieldset.appendChild(options);
            return fieldset;
        }

        function createDesign(policy) {
            const section = document.createElement('section');
            section.className = 'order-design';
            section.innerHTML = `
                <div class="order-design__toggle form-check form-switch">
                    <input class="form-check-input js-design-enabled" type="checkbox" role="switch" id="currentDesignEnabled">
                    <label class="form-check-label" for="currentDesignEnabled"><strong>Diseno personalizado</strong><small>${Number(policy.recargo_diseno) > 0 ? `Recargo: L. ${money(policy.recargo_diseno)}` : 'Sin recargo adicional'}</small></label>
                </div>
                <div class="order-design__fields js-design-fields" hidden>
                    <label><span>Color o combinacion</span><input class="form-control js-design-color" maxlength="150" placeholder="Ej. Rosa pastel y dorado"></label>
                    <label class="order-design__instructions"><span>Indicaciones del diseno</span><textarea class="form-control js-design-instructions" maxlength="1000" rows="3" placeholder="Forma, decoracion y otros detalles"></textarea></label>
                    ${policy.permite_imagen ? '<label class="order-design__file"><span>Imagen de referencia</span><input class="form-control js-design-file" type="file" accept="image/jpeg,image/png,image/webp"><small>JPG, PNG o WebP. Maximo 5 MB.</small></label>' : ''}
                </div>`;
            const toggle = section.querySelector('.js-design-enabled');
            toggle.dataset.surcharge = policy.recargo_diseno || 0;
            toggle.addEventListener('change', () => {
                section.querySelector('.js-design-fields').hidden = !toggle.checked;
                updateSurcharge();
            });
            return section;
        }

        function enforceMaximum(options, maximum) {
            const checked = [...options.querySelectorAll('input:checked')];
            options.querySelectorAll('input:not(:checked)').forEach((input) => { input.disabled = checked.length >= maximum; });
        }

        function updateSurcharge() {
            const optionTotal = [...container.querySelectorAll('.order-cake-option input:checked')]
                .reduce((sum, input) => sum + Number(input.dataset.surcharge || 0), 0);
            const designToggle = container.querySelector('.js-design-enabled:checked');
            const total = optionTotal + Number(designToggle?.dataset.surcharge || 0);
            const output = document.getElementById('customization-surcharge');
            if (output) output.textContent = `+ L. ${money(total)}`;
            return total;
        }

        function snapshot() {
            const choices = [...container.querySelectorAll('.order-cake-option input:checked')];
            for (const group of container.querySelectorAll('.order-cake-group')) {
                const selected = group.querySelectorAll('.order-cake-option input:checked').length;
                if (selected < Number(group.dataset.minimum) || selected > Number(group.dataset.maximum)) {
                    return { valid: false, message: `Completa ${group.querySelector('legend').textContent.replace(/^\d+/, '').trim()}.` };
                }
            }
            const designEnabled = Boolean(container.querySelector('.js-design-enabled:checked'));
            const design = {
                enabled: designEnabled,
                color: container.querySelector('.js-design-color')?.value.trim() || '',
                phrase: container.querySelector('.js-cake-message')?.value.trim() || '',
                instructions: container.querySelector('.js-design-instructions')?.value.trim() || '',
                file: container.querySelector('.js-design-file') || null,
                surcharge: Number(container.querySelector('.js-design-enabled')?.dataset.surcharge || 0),
            };
            const optionSurcharge = choices.reduce((sum, input) => sum + Number(input.dataset.surcharge || 0), 0);
            return { valid: true, choices, design, surcharge: optionSurcharge + (designEnabled ? design.surcharge : 0) };
        }

        render('');
        return { render, snapshot };
    }

    window.OrderCakeCustomizer = { create };
})();
