const pastelRadio = document.getElementById('tipoPastel');
const panaderiaRadio = document.getElementById('tipoPanaderia');
const typeRadios = Array.from(document.querySelectorAll('input[name="tipo_producto"]'));
const camposPastel = document.getElementById('camposPastel');
const camposPanaderia = document.getElementById('camposPanaderia');
const camposBebida = document.getElementById('camposBebida');
const camposBatido = document.getElementById('camposBatido');
const newProductCustomizationContainer = document.getElementById('newProductCustomizationGroups');
const newProductCustomizationEditor = newProductCustomizationContainer
    ? CakeConfigurationEditor.create(newProductCustomizationContainer)
    : null;
const allowCakeDesign = document.getElementById('allowCakeDesign');
const cakeDesignPolicyFields = document.getElementById('cakeDesignPolicyFields');
const beverageAllowMilk = document.getElementById('beverageAllowMilk');
const beverageMilkFields = document.getElementById('beverageMilkFields');
const beverageAllowFlavoring = document.getElementById('beverageAllowFlavoring');
const beverageFlavoringFields = document.getElementById('beverageFlavoringFields');
const inventoryProductSearch = document.getElementById('inventoryProductSearch');
const inventoryProductSearchClear = document.getElementById('inventoryProductSearchClear');
const inventoryProductSearchCount = document.getElementById('inventoryProductSearchCount');
const inventoryProductSearchEmpty = document.getElementById('inventoryProductSearchEmpty');
const inventoryProductRows = Array.from(document.querySelectorAll('[data-inventory-product]'));
const supplyWasteForm = document.getElementById('formRegistrarMermaInsumo');
const supplyWasteId = document.getElementById('supplyWasteId');
const supplyWasteName = document.getElementById('supplyWasteName');
const supplyWasteStock = document.getElementById('supplyWasteStock');
const supplyWasteQuantity = document.getElementById('supplyWasteQuantity');
const supplyCatalogForm = document.getElementById('formGestionVaso');
const supplyEditForm = document.getElementById('formEditarVaso');
const supplyCatalogModalElement = document.getElementById('modalGestionVasos');
const supplyEditModalElement = document.getElementById('modalEditarVaso');
let returnToSupplyCatalog = false;

async function sendSupplyCatalogAction(action, data) {
    const response = await fetch(`api.php?resource=inventario&action=${encodeURIComponent(action)}`, {
        method: 'POST',
        body: data,
    });
    const result = await response.json();
    if (!response.ok || result.status !== 'success') {
        throw new Error(result.message || 'No fue posible guardar el vaso.');
    }
    return result;
}

document.querySelectorAll('[data-quick-supply-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
        const panel = document.querySelector(`[data-quick-supply="${CSS.escape(button.dataset.quickSupplyToggle || '')}"]`);
        if (!panel) return;
        panel.hidden = !panel.hidden;
        if (!panel.hidden) panel.querySelector('[data-quick-supply-name]')?.focus();
    });
});

document.querySelectorAll('[data-quick-supply-save]').forEach((button) => {
    button.addEventListener('click', async () => {
        const panel = button.closest('[data-quick-supply]');
        const nameInput = panel?.querySelector('[data-quick-supply-name]');
        const minimumInput = panel?.querySelector('[data-quick-supply-minimum]');
        const usage = panel?.dataset.quickSupply || '';
        if (!(nameInput instanceof HTMLInputElement) || !(minimumInput instanceof HTMLInputElement)) return;
        if (!nameInput.value.trim()) {
            Swal.fire('Nombre requerido', 'Escribe un nombre para el nuevo vaso.', 'warning');
            nameInput.focus();
            return;
        }
        if (!minimumInput.reportValidity()) return;
        const data = new FormData();
        data.set('nombre', nameInput.value);
        data.set('tipo_uso', usage);
        data.set('stock_minimo', minimumInput.value);
        button.disabled = true;
        try {
            const result = await sendSupplyCatalogAction('crear_vaso', data);
            const select = document.querySelector(`[data-supply-select="${CSS.escape(usage)}"]`);
            if (select instanceof HTMLSelectElement) {
                const option = new Option(result.vaso.nombre, String(result.vaso.id_insumo), true, true);
                select.add(option);
                select.dispatchEvent(new Event('change', { bubbles: true }));
                select.dispatchEvent(new CustomEvent('app-select-sync'));
            }
            panel.hidden = true;
            nameInput.value = '';
            minimumInput.value = '10';
            await Swal.fire('Vaso agregado', result.message, 'success');
        } catch (error) {
            Swal.fire('Error', error.message || 'No fue posible guardar el vaso.', 'error');
        } finally {
            button.disabled = false;
        }
    });
});

function resetSupplyCatalogForm() {
    if (!supplyCatalogForm) return;
    supplyCatalogForm.reset();
    supplyCatalogForm.elements.stock_minimo.value = '10';
    supplyCatalogForm.querySelector('select')?.dispatchEvent(new CustomEvent('app-select-sync'));
}

document.querySelectorAll('[data-supply-edit]').forEach((button) => {
    button.addEventListener('click', () => {
        if (!supplyEditForm || !supplyEditModalElement) return;
        returnToSupplyCatalog = true;
        supplyEditForm.elements.id_insumo.value = button.dataset.id || '';
        supplyEditForm.elements.nombre.value = button.dataset.name || '';
        supplyEditForm.elements.tipo_uso.value = button.dataset.usage || 'bebida';
        supplyEditForm.elements.stock_minimo.value = button.dataset.minimum || '10';
        supplyEditForm.elements.tipo_uso.dispatchEvent(new CustomEvent('app-select-sync'));

        const showEditModal = () => {
            bootstrap.Modal.getOrCreateInstance(supplyEditModalElement).show();
            supplyEditModalElement.addEventListener('shown.bs.modal', () => supplyEditForm.elements.nombre.focus(), { once: true });
        };
        const catalogModal = supplyCatalogModalElement ? bootstrap.Modal.getInstance(supplyCatalogModalElement) : null;
        if (catalogModal && supplyCatalogModalElement.classList.contains('show')) {
            supplyCatalogModalElement.addEventListener('hidden.bs.modal', showEditModal, { once: true });
            catalogModal.hide();
        } else {
            showEditModal();
        }
    });
});

supplyEditModalElement?.addEventListener('hidden.bs.modal', () => {
    if (!returnToSupplyCatalog || !supplyCatalogModalElement) return;
    returnToSupplyCatalog = false;
    bootstrap.Modal.getOrCreateInstance(supplyCatalogModalElement).show();
});

function updateSupplyInPage(supply) {
    const id = String(supply.id_insumo);
    const usageLabel = supply.tipo_uso === 'batido' ? 'Batidos' : 'Bebidas';
    const row = document.querySelector(`[data-supply-row="${CSS.escape(id)}"]`);
    if (row) {
        row.querySelector('[data-supply-visible-name]').textContent = supply.nombre;
        row.querySelector('[data-supply-meta]').textContent = `${usageLabel} · ${supply.codigo}`;
        const editButton = row.querySelector('[data-supply-edit]');
        editButton.dataset.name = supply.nombre;
        editButton.dataset.usage = supply.tipo_uso;
        editButton.dataset.minimum = String(supply.stock_minimo);
        editButton.setAttribute('aria-label', `Editar ${supply.nombre}`);
        const statusButton = row.querySelector('[data-supply-status]');
        if (statusButton) {
            const action = statusButton.dataset.status === 'Activo' ? 'Activar' : 'Desactivar';
            statusButton.setAttribute('aria-label', `${action} ${supply.nombre}`);
            statusButton.title = `${action} vaso`;
        }
        const deleteButton = row.querySelector('[data-supply-delete]');
        if (deleteButton) {
            deleteButton.dataset.name = supply.nombre;
            deleteButton.setAttribute('aria-label', `Eliminar ${supply.nombre}`);
            deleteButton.title = 'Eliminar vaso';
        }
    }

    document.querySelectorAll(`[data-supply-stock-card="${CSS.escape(id)}"], [data-supply-restock-row="${CSS.escape(id)}"]`).forEach((container) => {
        const name = container.querySelector('[data-supply-visible-name]');
        if (name) name.textContent = supply.nombre;
        const wasteButton = container.querySelector('[data-supply-waste]');
        if (wasteButton) {
            wasteButton.dataset.supplyName = supply.nombre;
            wasteButton.setAttribute('aria-label', `Registrar merma de ${supply.nombre}`);
            wasteButton.title = `Registrar merma de ${supply.nombre}`;
        }
        const stockInput = container.querySelector('[data-supply-id]');
        if (stockInput && stockInput.tagName === 'INPUT') {
            stockInput.setAttribute('aria-label', `Cantidad de ${supply.nombre}`);
        }
    });

    document.querySelectorAll('[data-supply-select]').forEach((select) => {
        const existing = Array.from(select.options).find((option) => option.value === id);
        if (select.dataset.supplySelect === supply.tipo_uso) {
            if (existing) {
                existing.textContent = supply.nombre;
            } else {
                select.add(new Option(supply.nombre, id));
            }
        } else if (existing) {
            existing.remove();
        }
        select.dispatchEvent(new CustomEvent('app-select-sync'));
    });
}

supplyCatalogForm?.addEventListener('submit', async (event) => {
    event.preventDefault();
    if (!supplyCatalogForm.reportValidity()) return;
    const submit = supplyCatalogForm.querySelector('[type="submit"]');
    submit.disabled = true;
    try {
        const result = await sendSupplyCatalogAction('crear_vaso', new FormData(supplyCatalogForm));
        await Swal.fire('Vaso creado', result.message, 'success');
        location.reload();
    } catch (error) {
        Swal.fire('Error', error.message || 'No fue posible guardar el vaso.', 'error');
    } finally {
        submit.disabled = false;
    }
});

supplyEditForm?.addEventListener('submit', async (event) => {
    event.preventDefault();
    if (!supplyEditForm.reportValidity()) return;
    const submit = supplyEditForm.querySelector('[type="submit"]');
    submit.disabled = true;
    try {
        const result = await sendSupplyCatalogAction('actualizar_vaso', new FormData(supplyEditForm));
        updateSupplyInPage(result.vaso);
        returnToSupplyCatalog = false;
        bootstrap.Modal.getInstance(supplyEditModalElement)?.hide();
        await Swal.fire('Vaso actualizado', result.message, 'success');
        if (supplyCatalogModalElement) bootstrap.Modal.getOrCreateInstance(supplyCatalogModalElement).show();
    } catch (error) {
        Swal.fire('Error', error.message || 'No fue posible actualizar el vaso.', 'error');
    } finally {
        submit.disabled = false;
    }
});

document.querySelectorAll('[data-supply-status]').forEach((button) => {
    button.addEventListener('click', async () => {
        const nextStatus = button.dataset.status || '';
        const confirmation = await Swal.fire({
            icon: 'question',
            title: `${nextStatus === 'Activo' ? 'Activar' : 'Desactivar'} vaso`,
            text: nextStatus === 'Inactivo' ? 'Dejara de aparecer al registrar productos nuevos.' : 'Volvera a estar disponible para productos nuevos.',
            showCancelButton: true,
            confirmButtonText: 'Confirmar',
            cancelButtonText: 'Cancelar',
        });
        if (!confirmation.isConfirmed) return;
        const data = new FormData();
        data.set('id_insumo', button.dataset.id || '');
        data.set('estado', nextStatus);
        try {
            const result = await sendSupplyCatalogAction('cambiar_estado_vaso', data);
            await Swal.fire('Estado actualizado', result.message, 'success');
            location.reload();
        } catch (error) {
            Swal.fire('No se pudo cambiar el estado', error.message, 'error');
        }
    });
});

document.querySelectorAll('[data-supply-delete]').forEach((button) => {
    button.addEventListener('click', async () => {
        const confirmation = await Swal.fire({
            icon: 'warning',
            title: 'Eliminar vaso',
            text: `Se eliminara ${button.dataset.name || 'el vaso'} solamente si nunca ha sido utilizado.`,
            showCancelButton: true,
            confirmButtonText: 'Eliminar',
            cancelButtonText: 'Cancelar',
        });
        if (!confirmation.isConfirmed) return;
        const data = new FormData();
        data.set('id_insumo', button.dataset.id || '');
        try {
            const result = await sendSupplyCatalogAction('eliminar_vaso', data);
            await Swal.fire('Vaso eliminado', result.message, 'success');
            location.reload();
        } catch (error) {
            Swal.fire('No se pudo eliminar', error.message, 'error');
        }
    });
});

function normalizeInventorySearch(value) {
    return String(value || '')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLocaleLowerCase('es')
        .trim();
}

function filterInventoryProducts() {
    if (!inventoryProductSearch) return;
    const term = normalizeInventorySearch(inventoryProductSearch.value);
    let visible = 0;
    inventoryProductRows.forEach((row) => {
        const matches = !term || normalizeInventorySearch(row.dataset.search).includes(term);
        row.hidden = !matches;
        if (matches) visible += 1;
    });
    inventoryProductSearchClear.hidden = term === '';
    inventoryProductSearchEmpty.hidden = visible !== 0;
    inventoryProductSearchCount.textContent = `${visible} ${visible === 1 ? 'producto' : 'productos'}`;
}

inventoryProductSearch?.addEventListener('input', filterInventoryProducts);
inventoryProductSearch?.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape' || inventoryProductSearch.value === '') return;
    inventoryProductSearch.value = '';
    filterInventoryProducts();
});
inventoryProductSearchClear?.addEventListener('click', () => {
    inventoryProductSearch.value = '';
    filterInventoryProducts();
    inventoryProductSearch.focus();
});

document.querySelectorAll('[data-supply-waste]').forEach((button) => {
    button.addEventListener('click', () => {
        const stock = Math.max(0, Number(button.dataset.supplyStock) || 0);
        supplyWasteForm?.reset();
        supplyWasteId.value = button.dataset.supplyId || '';
        supplyWasteName.textContent = button.dataset.supplyName || 'Vaso';
        supplyWasteStock.textContent = button.dataset.supplyStock || '0';
        supplyWasteQuantity.max = String(Math.max(1, Math.floor(stock)));
        supplyWasteQuantity.value = '';
        const reason = document.getElementById('supplyWasteReason');
        if (reason instanceof HTMLSelectElement) reason.dispatchEvent(new CustomEvent('app-select-sync'));
    });
});

supplyWasteForm?.addEventListener('submit', async (event) => {
    event.preventDefault();
    if (!supplyWasteForm.reportValidity()) return;
    const submit = supplyWasteForm.querySelector('[type="submit"]');
    submit.disabled = true;
    try {
        const response = await fetch('api.php?resource=inventario&action=registrar_merma_insumo', {
            method: 'POST',
            body: new FormData(supplyWasteForm),
        });
        const result = await response.json();
        if (!response.ok || result.status !== 'success') {
            throw new Error(result.message || 'No fue posible registrar la merma.');
        }
        await Swal.fire('Merma registrada', result.message, 'success');
        location.reload();
    } catch (error) {
        Swal.fire('Error', error.message || 'No fue posible conectar con el servidor.', 'error');
        submit.disabled = false;
    }
});

function syncBeverageOptions() {
    const beverageActive = typeRadios.find((radio) => radio.checked)?.value === 'bebida';
    [
        [beverageAllowMilk, beverageMilkFields],
        [beverageAllowFlavoring, beverageFlavoringFields],
    ].forEach(([toggle, fields]) => {
        if (!toggle || !fields) return;
        toggle.disabled = !beverageActive;
        const enabled = beverageActive && toggle.checked;
        fields.hidden = !enabled;
        fields.querySelectorAll('input, textarea, select').forEach((control) => { control.disabled = !enabled; });
    });
}

function actualizarVistaTipoProducto() {
    const type = typeRadios.find((radio) => radio.checked)?.value || 'pastel';
    const sections = { pastel: camposPastel, panaderia: camposPanaderia, bebida: camposBebida, batido: camposBatido };
    Object.entries(sections).forEach(([sectionType, section]) => {
        if (!section) return;
        const active = type === sectionType;
        section.hidden = !active;
        section.style.display = active ? 'block' : 'none';
        section.querySelectorAll('input, select, textarea, button').forEach((control) => {
            control.disabled = !active;
            if (control instanceof HTMLSelectElement) control.dispatchEvent(new CustomEvent('app-select-sync'));
        });
    });
    document.querySelectorAll('[data-product-stock-input]').forEach((input) => {
        input.disabled = type === 'bebida' || type === 'batido';
        input.closest('.col-12')?.classList.toggle('opacity-50', input.disabled);
    });
    syncBeverageOptions();
}

typeRadios.forEach((radio) => radio.addEventListener('change', actualizarVistaTipoProducto));

actualizarVistaTipoProducto();

allowCakeDesign?.addEventListener('change', () => {
    cakeDesignPolicyFields?.classList.toggle('d-none', !allowCakeDesign.checked);
});

beverageAllowMilk?.addEventListener('change', syncBeverageOptions);
beverageAllowFlavoring?.addEventListener('change', syncBeverageOptions);

// 1. MANEJO DEL FORMULARIO DE NUEVO PRODUCTO
document.getElementById('formNuevoProducto').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    formData.set('configuracion', JSON.stringify(pastelRadio?.checked ? newProductCustomizationEditor?.getGroups() || [] : []));

    Swal.fire({
        title: 'Procesando...',
        text: 'Guardando el nuevo producto',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });

        fetch('api.php?resource=inventario&action=registrar', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            Swal.fire({
                icon: 'success',
                title: '¡Logrado!',
                text: data.message,
                confirmButtonColor: '#ff85a2' // Color principal adaptado
            }).then(() => {
                location.reload();
            });
        } else {
            Swal.fire('Error', data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire('Error', 'Hubo un problema con la conexión', 'error');
    });
});

// 2. MANEJO DEL FORMULARIO DE MERMAS (Separado y con Delegación de Eventos)
document.addEventListener('submit', function(e) {
    // Escucha cualquier envío de formulario y verifica si es de merma
    if (e.target && e.target.classList.contains('formRegistroMerma')) {
        e.preventDefault(); 
        
        const form = e.target;
        const btnSubmit = form.querySelector('button[type="submit"]');
        
        // Bloqueo visual del botón para evitar múltiples envíos
        if (btnSubmit) {
            btnSubmit.disabled = true;
            btnSubmit.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i>Procesando...';
        }

        fetch('api.php?resource=inventario&action=registrarMerma', {
            method: 'POST',
            body: new FormData(form)
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: '¡Merma Registrada!',
                    text: data.message,
                    confirmButtonColor: '#dc3545'
                }).then(() => {
                    location.reload(); 
                });
            } else {
                Swal.fire('Error', data.message, 'error');
                // Restaurar el botón si hay error
                if (btnSubmit) {
                    btnSubmit.disabled = false;
                    btnSubmit.innerHTML = 'Descontar Stock'; 
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire('Error', 'Hubo un problema de conexión con el servidor.', 'error');
            // Restaurar el botón si hay error
            if (btnSubmit) {
                btnSubmit.disabled = false;
                btnSubmit.innerHTML = 'Descontar Stock';
            }
        });
    }
});
