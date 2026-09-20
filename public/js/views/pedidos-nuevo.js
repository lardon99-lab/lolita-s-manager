document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('formNuevoPedido');
    const productSelect = document.getElementById('select-producto');
    const branchSelect = document.getElementById('order-branch');
    if (!form || !productSelect || !branchSelect) return;
    const configurations = JSON.parse(document.getElementById('order-configurations')?.textContent || '{}');
    const designPolicies = JSON.parse(document.getElementById('order-design-configurations')?.textContent || '{}');
    const customizer = OrderCakeCustomizer.create(
        document.getElementById('panel-opciones-producto'),
        document.getElementById('order-customization-groups'),
        configurations,
        designPolicies
    );
    let rowIndex = 0;

    const money = (value) => Number(value || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    function filterProducts() {
        const branchId = branchSelect.value;
        [...productSelect.options].forEach((option, index) => {
            if (index === 0) return;
            const branches = String(option.dataset.branches || '').split(',');
            option.hidden = branchId !== '' && !branches.includes(branchId);
        });
        if (productSelect.selectedOptions[0]?.hidden) productSelect.value = '';
        window.AppSelect?.sync(productSelect);
        customizer.render(productSelect.value, productSelect.selectedOptions[0]?.dataset.type || '');
    }

    function hiddenInput(name, value) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        input.value = String(value);
        return input;
    }

    function appendDesignInputs(cell, index, design) {
        cell.append(hiddenInput(`lineas[${index}][diseno][activo]`, design.enabled ? 1 : 0));
        cell.append(hiddenInput(`lineas[${index}][diseno][frase]`, design.phrase));
        if (!design.enabled) return;
        cell.append(hiddenInput(`lineas[${index}][diseno][color]`, design.color));
        cell.append(hiddenInput(`lineas[${index}][diseno][instrucciones]`, design.instructions));
        if (design.file?.files?.length) {
            design.file.name = `design_files[${index}]`;
            design.file.classList.add('d-none');
            cell.appendChild(design.file);
        }
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
        const customization = customizer.snapshot();
        if (!customization.valid) {
            Swal.fire('Personalizacion incompleta', customization.message, 'warning');
            return false;
        }
        if (customization.design.file?.files?.[0]?.size > 5 * 1024 * 1024) {
            Swal.fire('Imagen demasiado grande', 'La referencia debe pesar como maximo 5 MB.', 'warning');
            return false;
        }

        const basePrice = Number(selectedProduct.dataset.precio || 0);
        const subtotal = (basePrice + customization.surcharge) * quantity;
        const index = rowIndex++;
        const row = document.getElementById('tabla-detalles').querySelector('tbody').insertRow();
        row.className = 'order-detail-row';

        const productCell = row.insertCell();
        productCell.className = 'ps-3 py-3';
        productCell.dataset.label = 'Producto';
        productCell.append(hiddenInput(`lineas[${index}][producto]`, productSelect.value));
        customization.choices.forEach((choice) => productCell.append(hiddenInput(`lineas[${index}][opciones][]`, choice.value)));
        appendDesignInputs(productCell, index, customization.design);
        const name = document.createElement('div');
        name.className = 'fw-bold text-dark fs-6';
        name.textContent = selectedProduct.dataset.nombre || selectedProduct.textContent.split('(')[0].trim();
        const price = document.createElement('div');
        price.className = 'small text-muted mt-1';
        price.textContent = `Base: L. ${money(basePrice)}${customization.surcharge > 0 ? ` + L. ${money(customization.surcharge)}` : ''}`;
        productCell.append(name, price);

        const detailsCell = row.insertCell();
        detailsCell.className = 'py-3';
        detailsCell.dataset.label = 'Personalizacion';
        const parts = customization.choices.map((choice) => `${choice.dataset.groupName}: ${choice.dataset.optionName}`);
        if (customization.design.phrase) parts.push(`Frase: ${customization.design.phrase}`);
        if (customization.design.enabled) {
            parts.push(`Diseno: ${customization.design.color || 'personalizado'}`);
            if (customization.design.file?.files?.length) parts.push('Referencia adjunta');
        }
        const summary = document.createElement('div');
        summary.className = 'small text-muted mb-2';
        summary.textContent = parts.length ? parts.join(' | ') : 'Sin opciones adicionales';
        const details = document.createElement('textarea');
        details.name = `lineas[${index}][personalizacion]`;
        details.className = 'form-control form-control-sm border-0 bg-light shadow-sm';
        details.rows = 2;
        details.maxLength = 1000;
        details.placeholder = 'Notas adicionales para produccion';
        detailsCell.append(summary, details);

        const quantityCell = row.insertCell();
        quantityCell.className = 'text-center align-middle py-3';
        quantityCell.dataset.label = 'Cantidad';
        quantityCell.append(hiddenInput(`lineas[${index}][cantidad]`, quantity));
        const badge = document.createElement('span');
        badge.className = 'order-quantity-badge';
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
        customizer.render('', '');
        calculateTotal();
        return true;
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
    productSelect.addEventListener('change', () => customizer.render(productSelect.value, productSelect.selectedOptions[0]?.dataset.type || ''));
    filterProducts();
});
