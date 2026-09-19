document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('formNuevoPedido');
    const productSelect = document.getElementById('select-producto');
    const branchSelect = document.getElementById('order-branch');
    const configurations = JSON.parse(document.getElementById('order-configurations')?.textContent || '{}');
    let rowIndex = 0;

    const money = (value) => Number(value || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    function filterProducts() {
        const branchId = branchSelect.value;
        [...productSelect.options].forEach((option, index) => {
            if (index === 0) return;
            const branches = String(option.dataset.branches || '').split(',');
            option.hidden = branchId !== '' && !branches.includes(branchId);
        });
        if (productSelect.selectedOptions[0]?.hidden) {
            productSelect.value = '';
            window.AppSelect?.sync(productSelect);
        }
        renderCustomization();
    }

    function renderCustomization() {
        const panel = document.getElementById('panel-opciones-producto');
        const container = document.getElementById('order-customization-groups');
        const groups = configurations[productSelect.value] || [];
        container.replaceChildren();
        panel.classList.toggle('d-none', groups.length === 0);
        groups.forEach((group) => container.appendChild(createGroup(group)));
        updateCustomizationSurcharge();
    }

    function createGroup(group) {
        const wrapper = document.createElement('fieldset');
        wrapper.className = 'order-customization-group';
        const legend = document.createElement('legend');
        legend.className = 'small fw-bold text-muted mb-2';
        legend.textContent = `${group.nombre}${Number(group.minimo_selecciones) > 0 ? ' *' : ''}`;
        wrapper.appendChild(legend);
        const options = document.createElement('div');
        options.className = 'order-customization-options';
        (group.opciones || []).forEach((option) => {
            const label = document.createElement('label');
            label.className = 'order-customization-option';
            const input = document.createElement('input');
            input.className = 'form-check-input';
            input.type = Number(group.maximo_selecciones) === 1 ? 'radio' : 'checkbox';
            input.name = `current-group-${group.id_grupo}`;
            input.value = option.id_opcion;
            input.dataset.surcharge = option.recargo;
            input.dataset.optionName = option.nombre;
            input.dataset.groupName = group.nombre;
            input.required = Number(group.minimo_selecciones) > 0 && input.type === 'radio';
            input.checked = Boolean(Number(option.predeterminada));
            input.addEventListener('change', () => {
                if (input.type === 'checkbox') enforceMaximum(options, Number(group.maximo_selecciones));
                updateCustomizationSurcharge();
            });
            const text = document.createElement('span');
            text.textContent = `${option.nombre}${Number(option.recargo) > 0 ? ` (+ L. ${money(option.recargo)})` : ''}`;
            label.append(input, text);
            options.appendChild(label);
        });
        wrapper.appendChild(options);
        return wrapper;
    }

    function enforceMaximum(container, maximum) {
        const checked = [...container.querySelectorAll('input:checked')];
        const reached = checked.length >= maximum;
        container.querySelectorAll('input:not(:checked)').forEach((input) => { input.disabled = reached; });
    }

    function selectedOptions() {
        return [...document.querySelectorAll('#order-customization-groups input:checked')];
    }

    function updateCustomizationSurcharge() {
        const surcharge = selectedOptions().reduce((sum, input) => sum + Number(input.dataset.surcharge || 0), 0);
        document.getElementById('customization-surcharge').textContent = `+ L. ${money(surcharge)}`;
    }

    function validateSelections(groups) {
        for (const group of groups) {
            const selected = selectedOptions().filter((input) => input.dataset.groupName === group.nombre).length;
            if (selected < Number(group.minimo_selecciones) || selected > Number(group.maximo_selecciones)) {
                Swal.fire('Seleccion incompleta', `Revisa las opciones de ${group.nombre}.`, 'warning');
                return false;
            }
        }
        return true;
    }

    function addOrderLine() {
        const selectedProduct = productSelect.selectedOptions[0];
        if (!selectedProduct || !productSelect.value || !branchSelect.value) {
            Swal.fire('Atencion', 'Selecciona una sucursal y un producto disponible.', 'warning');
            return false;
        }
        const quantity = Number.parseInt(document.getElementById('cant-producto').value, 10);
        if (!Number.isInteger(quantity) || quantity < 1 || quantity > 1000) {
            Swal.fire('Cantidad no valida', 'Ingresa una cantidad entre 1 y 1000.', 'warning');
            return false;
        }
        const groups = configurations[productSelect.value] || [];
        if (!validateSelections(groups)) return false;
        const basePrice = Number(selectedProduct.dataset.precio || 0);
        const choices = selectedOptions();
        const surcharge = choices.reduce((sum, input) => sum + Number(input.dataset.surcharge || 0), 0);
        const subtotal = (basePrice + surcharge) * quantity;
        const index = rowIndex++;
        const row = document.getElementById('tabla-detalles').querySelector('tbody').insertRow();
        row.className = 'order-detail-row';

        const productCell = row.insertCell();
        productCell.className = 'ps-3 py-3';
        productCell.dataset.label = 'Producto';
        productCell.append(hiddenInput(`lineas[${index}][producto]`, productSelect.value));
        const name = document.createElement('div');
        name.className = 'fw-bold text-dark fs-6';
        name.textContent = selectedProduct.dataset.nombre || selectedProduct.textContent.split('(')[0].trim();
        const price = document.createElement('div');
        price.className = 'small text-muted mt-1';
        price.textContent = `Base: L. ${money(basePrice)}${surcharge > 0 ? ` + L. ${money(surcharge)}` : ''}`;
        productCell.append(name, price);
        choices.forEach((choice) => productCell.append(hiddenInput(`lineas[${index}][opciones][]`, choice.value)));

        const detailsCell = row.insertCell();
        detailsCell.className = 'py-3';
        detailsCell.dataset.label = 'Personalización';
        const summary = document.createElement('div');
        summary.className = 'small text-muted mb-2';
        summary.textContent = choices.length ? choices.map((choice) => `${choice.dataset.groupName}: ${choice.dataset.optionName}`).join(' | ') : 'Sin opciones adicionales';
        const details = document.createElement('textarea');
        details.name = `lineas[${index}][personalizacion]`;
        details.className = 'form-control form-control-sm border-0 bg-light shadow-sm';
        details.rows = 2;
        details.maxLength = 1000;
        details.placeholder = 'Colores, dedicatoria y detalles especiales';
        detailsCell.append(summary, details);

        const quantityCell = row.insertCell();
        quantityCell.className = 'text-center align-middle py-3';
        quantityCell.dataset.label = 'Cantidad';
        quantityCell.append(hiddenInput(`lineas[${index}][cantidad]`, quantity));
        const badge = document.createElement('span');
        badge.className = 'badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-circle d-inline-flex align-items-center justify-content-center';
        badge.style.cssText = 'width:32px;height:32px;font-size:.9rem';
        badge.textContent = quantity;
        quantityCell.appendChild(badge);

        const subtotalCell = row.insertCell();
        subtotalCell.className = 'subtotal-fila text-end fw-bold align-middle text-success py-3';
        subtotalCell.dataset.label = 'Subtotal';
        subtotalCell.dataset.valor = String(subtotal);
        subtotalCell.textContent = `L. ${money(subtotal)}`;
        const actionCell = row.insertCell();
        actionCell.className = 'text-end pe-3 align-middle py-3';
        actionCell.dataset.label = 'Acciones';
        const remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'btn btn-sm btn-light text-danger rounded-circle border';
        remove.title = 'Eliminar';
        remove.innerHTML = '<i class="fa-solid fa-trash-can" aria-hidden="true"></i>';
        remove.addEventListener('click', () => { row.remove(); calculateTotal(); });
        actionCell.appendChild(remove);

        productSelect.value = '';
        window.AppSelect?.sync(productSelect);
        document.getElementById('cant-producto').value = '1';
        renderCustomization();
        calculateTotal();
        return true;
    }

    function hiddenInput(name, value) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        input.value = String(value);
        return input;
    }

    function calculateTotal() {
        const total = [...document.querySelectorAll('.subtotal-fila')].reduce((sum, cell) => sum + Number(cell.dataset.valor || 0), 0);
        document.getElementById('total-pedido').textContent = money(total);
        document.getElementById('input-total').value = total.toFixed(2);
        window.gestionarPago();
    }

    window.gestionarPago = function gestionarPago() {
        const type = document.getElementById('tipo_pago').value;
        const depositContainer = document.getElementById('contenedor-abono');
        const methodContainer = document.getElementById('contenedor-metodo-pago');
        const deposit = document.getElementById('monto_abono');
        const total = Number(document.getElementById('input-total').value || 0);
        depositContainer.style.display = type === 'Abonado' ? 'block' : 'none';
        methodContainer.style.display = type === 'Pendiente' ? 'none' : 'block';
        if (type === 'Pagado') deposit.value = total.toFixed(2);
        if (type === 'Pendiente') deposit.value = '0';
        if (type === 'Abonado' && (Number(deposit.value) <= 0 || Number(deposit.value) > total)) deposit.value = (total * .5).toFixed(2);
    };

    document.getElementById('cliente-input').addEventListener('input', (event) => {
        const match = [...document.getElementById('lista-clientes').options].find((option) => option.value === event.target.value);
        document.getElementById('id_cliente_real').value = match?.dataset.id || '';
    });
    document.getElementById('add-order-line').addEventListener('click', addOrderLine);
    form.addEventListener('submit', (event) => {
        const clientId = document.getElementById('id_cliente_real').value;
        let hasItems = document.querySelector('#tabla-detalles tbody tr') !== null;
        if (!hasItems && productSelect.value) {
            if (!addOrderLine()) {
                event.preventDefault();
                return;
            }
            hasItems = true;
        }
        if (!clientId || !hasItems) {
            event.preventDefault();
            Swal.fire('Pedido incompleto', !clientId ? 'Selecciona un cliente valido.' : 'Agrega al menos un producto.', 'error');
        }
    });

    branchSelect.addEventListener('change', filterProducts);
    productSelect.addEventListener('change', renderCustomization);
    filterProducts();
});
