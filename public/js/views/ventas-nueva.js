document.addEventListener('DOMContentLoaded', () => {
    const productSelect = document.getElementById('id_producto');
    if (!productSelect) return;

    const quantityInput = document.getElementById('cantidad');
    const priceLabel = document.getElementById('label-precio');
    const searchInput = document.getElementById('buscador-producto');
    const noResults = document.getElementById('no-results');
    const addButton = document.getElementById('btnAgregarCarrito');
    const cartBody = document.getElementById('cuerpoCarrito');
    const checkoutButton = document.getElementById('btnCobrar');
    const totalLabel = document.getElementById('total-venta');
    const itemsCount = document.getElementById('items-count');
    const modalElement = document.getElementById('salesCustomizationModal');
    const modalGroups = document.getElementById('salesCustomizationGroups');
    const modalTitle = document.getElementById('salesCustomizationTitle');
    const modalPrice = document.getElementById('salesCustomizationPrice');
    const modalAdd = document.getElementById('salesCustomizationAdd');
    const options = Array.from(productSelect.options);
    const configurations = JSON.parse(document.getElementById('sales-product-configurations')?.textContent || '{}');
    const customizationModal = modalElement && window.bootstrap ? bootstrap.Modal.getOrCreateInstance(modalElement) : null;
    const money = new Intl.NumberFormat('es-HN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    let cart = [];
    let pendingProduct = null;

    const selectedOption = () => productSelect.options[productSelect.selectedIndex];
    const productName = (option) => option.textContent.split('—')[0].trim();
    const formatMoney = (value) => `L. ${money.format(Number(value) || 0)}`;
    const escapeHtml = (value) => {
        const element = document.createElement('span');
        element.textContent = String(value ?? '');
        return element.innerHTML;
    };

    function selectedQuantity() {
        const quantity = Number.parseInt(quantityInput.value, 10);
        return Number.isInteger(quantity) && quantity > 0 ? quantity : 0;
    }

    function unitsInCart(stockKey) {
        return cart.filter((item) => item.stockKey === stockKey).reduce((sum, item) => sum + item.cantidad, 0);
    }

    function updateSelectedProduct() {
        const option = selectedOption();
        if (!option?.value) {
            priceLabel.textContent = 'L. 0.00';
            return;
        }
        priceLabel.textContent = formatMoney(option.dataset.precio);
        quantityInput.max = String(Math.max(0, Number.parseInt(option.dataset.stock, 10) || 0));
    }

    searchInput?.addEventListener('input', () => {
        const term = searchInput.value.trim().toLocaleLowerCase('es');
        let firstMatch = null;
        let matches = 0;
        options.forEach((option) => {
            if (!option.value) return;
            const visible = option.textContent.toLocaleLowerCase('es').includes(term);
            option.hidden = !visible;
            if (visible) {
                matches += 1;
                firstMatch ||= option;
            }
        });
        noResults?.classList.toggle('d-none', matches > 0);
        if (term && firstMatch) productSelect.value = firstMatch.value;
        updateSelectedProduct();
    });
    productSelect.addEventListener('change', updateSelectedProduct);

    function calculateCustomization(groups, selectedIds) {
        let surcharge = 0;
        const labels = [];
        groups.forEach((group) => {
            const selected = group.opciones.filter((option) => selectedIds.includes(String(option.id_opcion)));
            selected.forEach((option) => {
                surcharge += Number(option.recargo) || 0;
                labels.push(`${group.nombre}: ${option.nombre}`);
            });
            const included = Number.parseInt(group.selecciones_incluidas, 10) || 0;
            surcharge += Math.max(0, selected.length - included) * (Number(group.recargo_seleccion_extra) || 0);
        });
        return { surcharge, labels };
    }

    const selectedCustomizationIds = () => Array.from(modalGroups.querySelectorAll('input:checked')).map((input) => input.value);

    function updateCustomizationPrice() {
        if (!pendingProduct) return;
        const result = calculateCustomization(pendingProduct.groups, selectedCustomizationIds());
        modalPrice.textContent = formatMoney(pendingProduct.basePrice + result.surcharge);
    }

    function renderCustomization(option, quantity, groups) {
        pendingProduct = {
            id: option.value,
            name: productName(option),
            quantity,
            basePrice: Number(option.dataset.precio) || 0,
            stock: Number.parseInt(option.dataset.stock, 10) || 0,
            stockKey: option.dataset.stockKey || `producto:${option.value}`,
            groups,
        };
        modalTitle.textContent = pendingProduct.name;
        modalGroups.innerHTML = groups.map((group, groupIndex) => {
            const maximum = Number.parseInt(group.maximo_selecciones, 10) || 1;
            const required = Number.parseInt(group.minimo_selecciones, 10) > 0;
            const inputType = maximum === 1 ? 'radio' : 'checkbox';
            const help = maximum === 1 ? 'Elige una opción' : `Elige hasta ${maximum} opciones`;
            return `<fieldset class="sales-option-group" data-max="${maximum}">
                <legend><span>${escapeHtml(group.nombre)}</span><small>${help}${required ? ' · Obligatorio' : ''}</small></legend>
                <div class="sales-option-grid">${group.opciones.map((choice) => {
                    const id = `sale-option-${groupIndex}-${choice.id_opcion}`;
                    const checked = Number(choice.predeterminada) === 1 ? ' checked' : '';
                    const surcharge = Number(choice.recargo) || 0;
                    return `<label class="sales-option-card" for="${id}">
                        <input id="${id}" type="${inputType}" name="sale_group_${groupIndex}" value="${choice.id_opcion}"${checked}>
                        <span><strong>${escapeHtml(choice.nombre)}</strong><small>${surcharge > 0 ? `+ ${formatMoney(surcharge)}` : 'Sin recargo individual'}</small></span>
                        <i class="fa-solid fa-check" aria-hidden="true"></i>
                    </label>`;
                }).join('')}</div>
            </fieldset>`;
        }).join('');
        modalGroups.querySelectorAll('input').forEach((input) => input.addEventListener('change', (event) => {
            const group = event.target.closest('.sales-option-group');
            const maximum = Number.parseInt(group.dataset.max, 10) || 1;
            if (group.querySelectorAll('input:checked').length > maximum) {
                event.target.checked = false;
                Swal.fire('Límite alcanzado', `Puedes seleccionar hasta ${maximum} opciones en este grupo.`, 'warning');
            }
            updateCustomizationPrice();
        }));
        updateCustomizationPrice();
        customizationModal?.show();
    }

    function validateStock(stockKey, quantity, stock) {
        if (unitsInCart(stockKey) + quantity > stock) {
            Swal.fire('Stock insuficiente', 'La cantidad total supera las unidades disponibles del insumo o producto.', 'warning');
            return false;
        }
        return true;
    }

    function addCartLine(product, selectedIds = []) {
        if (!validateStock(product.stockKey, product.quantity, product.stock)) return;
        const customization = calculateCustomization(product.groups || [], selectedIds);
        const price = product.basePrice + customization.surcharge;
        const signature = `${product.id}:${selectedIds.slice().sort().join(',')}`;
        const existing = cart.find((item) => item.signature === signature);
        if (existing) existing.cantidad += product.quantity;
        else cart.push({
            id: product.id,
            nombre: product.name,
            cantidad: product.quantity,
            precio: price,
            stock: product.stock,
            stockKey: product.stockKey,
            opciones: selectedIds.map(Number),
            personalizacion: customization.labels,
            signature,
        });
        customizationModal?.hide();
        pendingProduct = null;
        renderCart();
    }

    addButton?.addEventListener('click', () => {
        const option = selectedOption();
        const quantity = selectedQuantity();
        if (!option?.value) return void Swal.fire('Atención', 'Selecciona un producto primero.', 'warning');
        if (!quantity) return void Swal.fire('Atención', 'Ingresa una cantidad válida.', 'warning');
        const stock = Number.parseInt(option.dataset.stock, 10) || 0;
        const stockKey = option.dataset.stockKey || `producto:${option.value}`;
        if (!validateStock(stockKey, quantity, stock)) return;
        const groups = configurations[option.value] || [];
        const product = { id: option.value, name: productName(option), quantity, basePrice: Number(option.dataset.precio) || 0, stock, stockKey, groups };
        if (groups.length) renderCustomization(option, quantity, groups);
        else addCartLine(product);
    });

    modalAdd?.addEventListener('click', () => {
        if (!pendingProduct) return;
        const selectedIds = selectedCustomizationIds();
        for (const group of pendingProduct.groups) {
            const validIds = group.opciones.map((option) => String(option.id_opcion));
            const count = selectedIds.filter((id) => validIds.includes(id)).length;
            if (count < (Number.parseInt(group.minimo_selecciones, 10) || 0)) {
                Swal.fire('Personalización incompleta', `Selecciona una opción en ${group.nombre}.`, 'warning');
                return;
            }
        }
        addCartLine(pendingProduct, selectedIds);
    });

    function renderCart() {
        if (!cart.length) {
            cartBody.innerHTML = '<tr class="sales-cart-empty"><td colspan="5"><i class="fa-solid fa-basket-shopping" aria-hidden="true"></i><span>El carrito está vacío</span></td></tr>';
        } else {
            cartBody.innerHTML = cart.map((item, index) => `<tr class="sales-cart-item">
                <td data-label="Producto"><strong>${escapeHtml(item.nombre)}</strong>${item.personalizacion.length ? `<small class="sales-cart-item__options">${item.personalizacion.map(escapeHtml).join(' · ')}</small>` : ''}</td>
                <td data-label="Cant." class="text-center"><span class="sales-quantity-chip">${item.cantidad}</span></td>
                <td data-label="Precio" class="text-end">${formatMoney(item.precio)}</td>
                <td data-label="Subtotal" class="text-end fw-bold text-primary">${formatMoney(item.precio * item.cantidad)}</td>
                <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger sales-remove" data-index="${index}" aria-label="Quitar ${escapeHtml(item.nombre)}"><i class="fa-solid fa-trash-can"></i></button></td>
            </tr>`).join('');
        }
        const total = cart.reduce((sum, item) => sum + item.precio * item.cantidad, 0);
        const units = cart.reduce((sum, item) => sum + item.cantidad, 0);
        totalLabel.textContent = formatMoney(total);
        itemsCount.textContent = `${units} ${units === 1 ? 'artículo' : 'artículos'}`;
        checkoutButton.disabled = cart.length === 0;
    }

    cartBody?.addEventListener('click', (event) => {
        const button = event.target.closest('.sales-remove');
        if (!button) return;
        cart.splice(Number(button.dataset.index), 1);
        renderCart();
    });

    checkoutButton?.addEventListener('click', async () => {
        if (!cart.length) return;
        const paymentMethod = document.querySelector('input[name="metodo_pago"]:checked')?.value || 'Efectivo';
        const confirmation = await Swal.fire({ title: 'Confirmar venta', text: `Total a cobrar: ${totalLabel.textContent}`, icon: 'question', showCancelButton: true, confirmButtonText: 'Cobrar', cancelButtonText: 'Revisar' });
        if (!confirmation.isConfirmed) return;
        checkoutButton.disabled = true;
        checkoutButton.innerHTML = '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span>Procesando...';
        try {
            const response = await fetch('api.php?resource=ventas&action=procesarVenta', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
                body: JSON.stringify({
                    id_sucursal: document.getElementById('id_sucursal').value,
                    metodo_pago: paymentMethod,
                    productos: cart.map(({ id, cantidad, opciones }) => ({ id, cantidad, opciones })),
                }),
            });
            const data = await response.json();
            if (!response.ok || data.status !== 'success') throw new Error(data.message || 'No fue posible registrar la venta.');
            await Swal.fire({ title: 'Venta registrada', text: data.message, icon: 'success', timer: 1500, showConfirmButton: false });
            location.reload();
        } catch (error) {
            Swal.fire('Error', error.message || 'Fallo de conexión.', 'error');
            checkoutButton.disabled = false;
            checkoutButton.innerHTML = '<i class="fa-solid fa-cash-register" aria-hidden="true"></i>Finalizar cobro';
        }
    });

    updateSelectedProduct();
    renderCart();
});
