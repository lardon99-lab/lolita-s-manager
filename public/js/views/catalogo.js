document.addEventListener('DOMContentLoaded', () => {
    const initialTab = Array.from(document.querySelectorAll('.catalog-tabs [data-bs-target]'))
        .find((button) => button.dataset.bsTarget === window.location.hash);
    if (initialTab) bootstrap.Tab.getOrCreateInstance(initialTab).show();

    const productForm = document.getElementById('productForm');
    const productModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('productModal'));
    document.querySelectorAll('.js-edit-product').forEach((button) => button.addEventListener('click', () => {
        const product = JSON.parse(button.dataset.product);
        document.getElementById('productId').value = product.id_producto;
        document.getElementById('productName').value = product.nombre_producto;
        const category = document.getElementById('productCategory');
        category.value = product.id_categoria;
        window.AppSelect?.sync(category);
        document.getElementById('productPrice').value = product.precio_base;
        document.getElementById('productLife').value = product.dias_vida_util;
        document.getElementById('productDescription').value = product.descripcion || '';
        productModal.show();
    }));
    productForm.addEventListener('submit', (event) => {
        event.preventDefault();
        submitCatalog('actualizar_producto', new FormData(productForm));
    });
    document.querySelectorAll('.js-state-product').forEach((button) => button.addEventListener('click', () => {
        const body = new FormData();
        body.set('id_producto', button.dataset.id);
        body.set('estado', button.dataset.state);
        submitCatalog('estado_producto', body);
    }));

    initializeCustomizationEditor();

    const categoryForm = document.getElementById('categoryForm');
    if (categoryForm) {
        const categoryModalElement = document.getElementById('categoryModal');
        const categoryModal = bootstrap.Modal.getOrCreateInstance(categoryModalElement);
        categoryModalElement.addEventListener('hidden.bs.modal', () => categoryForm.reset());
        document.querySelectorAll('.js-edit-category').forEach((button) => button.addEventListener('click', () => {
            document.getElementById('categoryId').value = button.dataset.id;
            document.getElementById('categoryName').value = button.dataset.name;
            categoryModal.show();
        }));
        categoryForm.addEventListener('submit', (event) => {
            event.preventDefault();
            submitCatalog('guardar_categoria', new FormData(categoryForm));
        });
        document.querySelectorAll('.js-state-category').forEach((button) => button.addEventListener('click', () => {
            const body = new FormData();
            body.set('id_categoria', button.dataset.id);
            body.set('estado', button.dataset.state);
            submitCatalog('estado_categoria', body);
        }));
    }

    initializeSupplyCatalog();

    const pageUrl = new URL(window.location.href);
    const openNewSupplyForm = pageUrl.searchParams.get('nuevo_vaso') === '1';
    if (openNewSupplyForm) {
        pageUrl.searchParams.delete('nuevo_vaso');
        window.history.replaceState(null, '', `${pageUrl.pathname}${pageUrl.search}${pageUrl.hash}`);
        bootstrap.Modal.getOrCreateInstance(document.getElementById('newSupplyModal')).show();
    }
});

function initializeSupplyCatalog() {
    const createForm = document.getElementById('newSupplyForm');
    const editForm = document.getElementById('editSupplyForm');
    const createModalElement = document.getElementById('newSupplyModal');
    const editModalElement = document.getElementById('editSupplyModal');
    if (!createForm || !editForm || !createModalElement || !editModalElement) return;

    const createModal = bootstrap.Modal.getOrCreateInstance(createModalElement);
    const editModal = bootstrap.Modal.getOrCreateInstance(editModalElement);

    createModalElement.addEventListener('show.bs.modal', () => {
        createForm.reset();
        createForm.elements.stock_minimo.value = '10';
        window.AppSelect?.sync(createForm.elements.tipo_uso);
    });

    document.querySelectorAll('.js-edit-supply').forEach((button) => button.addEventListener('click', () => {
        let supply;
        try {
            supply = JSON.parse(button.dataset.supply || '{}');
        } catch {
            Swal.fire('Error', 'No fue posible cargar los datos del vaso.', 'error');
            return;
        }
        editForm.elements.id_insumo.value = supply.id_insumo || '';
        editForm.elements.nombre.value = supply.nombre || '';
        editForm.elements.tipo_uso.value = supply.tipo_uso || 'bebida';
        const minimumStock = Number.parseInt(supply.stock_minimo, 10);
        editForm.elements.stock_minimo.value = Number.isFinite(minimumStock) ? String(minimumStock) : '10';
        window.AppSelect?.sync(editForm.elements.tipo_uso);
        editModalElement.addEventListener('shown.bs.modal', () => editForm.elements.nombre.focus(), { once: true });
        editModal.show();
    }));

    createForm.addEventListener('submit', (event) => {
        event.preventDefault();
        if (createForm.reportValidity()) submitSupply('crear_vaso', createForm);
    });

    editForm.addEventListener('submit', (event) => {
        event.preventDefault();
        if (editForm.reportValidity()) submitSupply('actualizar_vaso', editForm);
    });

    document.querySelectorAll('.js-state-supply').forEach((button) => button.addEventListener('click', async () => {
        const nextState = button.dataset.state || '';
        const confirmation = await Swal.fire({
            icon: 'question',
            title: nextState === 'Activo' ? 'Reactivar tipo de vaso' : 'Desactivar tipo de vaso',
            text: nextState === 'Activo'
                ? 'Volverá a estar disponible para productos nuevos en todas las sucursales.'
                : 'El cambio aplica a todas las sucursales. No se puede desactivar mientras haya productos activos vinculados.',
            showCancelButton: true,
            confirmButtonText: nextState === 'Activo' ? 'Reactivar' : 'Desactivar',
            cancelButtonText: 'Cancelar',
        });
        if (!confirmation.isConfirmed) return;
        const data = new FormData();
        data.set('id_insumo', button.dataset.id || '');
        data.set('estado', nextState);
        submitSupply('cambiar_estado_vaso', data, nextState === 'Activo' ? 'Tipo reactivado' : 'Tipo desactivado', button);
    }));

    document.querySelectorAll('.js-delete-supply').forEach((button) => button.addEventListener('click', async () => {
        const confirmation = await Swal.fire({
            icon: 'warning',
            title: 'Eliminar tipo de vaso',
            text: `Se eliminará ${button.dataset.name || 'este tipo'} solo si no tiene productos vinculados, existencias ni movimientos registrados.`,
            showCancelButton: true,
            confirmButtonText: 'Eliminar',
            cancelButtonText: 'Cancelar',
        });
        if (!confirmation.isConfirmed) return;
        const data = new FormData();
        data.set('id_insumo', button.dataset.id || '');
        submitSupply('eliminar_vaso', data, 'Tipo eliminado', button);
    }));

    async function submitSupply(action, source, successTitle = 'Cambios guardados', trigger = null) {
        const submit = source instanceof HTMLFormElement ? source.querySelector('[type="submit"]') : null;
        if (submit) submit.disabled = true;
        if (trigger) trigger.disabled = true;
        const body = source instanceof HTMLFormElement ? new FormData(source) : source;
        try {
            const response = await fetch(`api.php?resource=catalogo&action=${encodeURIComponent(action)}`, { method: 'POST', body });
            const result = await response.json();
            if (!response.ok || result.status !== 'success') throw new Error(result.message || 'No fue posible guardar el tipo de vaso.');
            if (action === 'crear_vaso') createModal.hide();
            if (action === 'actualizar_vaso') editModal.hide();
            await Swal.fire(successTitle, result.message, 'success');
            if (['crear_vaso', 'actualizar_vaso', 'cambiar_estado_vaso', 'eliminar_vaso'].includes(action)) {
                window.history.replaceState(null, '', `${window.location.pathname}${window.location.search}#suppliesTab`);
            }
            location.reload();
        } catch (error) {
            Swal.fire('Error', error.message || 'No fue posible completar la operación.', 'error');
        } finally {
            if (submit) submit.disabled = false;
            if (trigger) trigger.disabled = false;
        }
    }
}

function initializeCustomizationEditor() {
    const modalElement = document.getElementById('customizationModal');
    const form = document.getElementById('customizationForm');
    const groupsContainer = document.getElementById('customizationGroups');
    if (!modalElement || !form || !groupsContainer) return;
    const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
    const editor = ProductCustomizationEditor.create(groupsContainer);

    document.querySelectorAll('.js-customize-product').forEach((button) => button.addEventListener('click', async () => {
        document.getElementById('customizationProductId').value = button.dataset.id;
        document.getElementById('customizationProductName').textContent = button.dataset.name || '';
        editor.setLoading();
        modal.show();
        try {
            const response = await fetch(`api.php?resource=catalogo&action=configuracion_producto&id_producto=${encodeURIComponent(button.dataset.id)}`, { headers: { Accept: 'application/json' } });
            const data = await response.json();
            if (!response.ok || data.status !== 'success') throw new Error(data.message || 'No fue posible cargar las opciones.');
            editor.setGroups(data.configuracion || []);
        } catch (error) {
            editor.setGroups();
            Swal.fire('Error', error.message || 'No fue posible cargar las opciones.', 'error');
            modal.hide();
        }
    }));

    document.getElementById('addCustomizationGroup').addEventListener('click', () => editor.addGroup());
    form.addEventListener('submit', (event) => {
        event.preventDefault();
        const body = new FormData();
        body.set('id_producto', document.getElementById('customizationProductId').value);
        body.set('configuracion', JSON.stringify(editor.getGroups()));
        submitCatalog('guardar_personalizacion', body);
    });
}

async function submitCatalog(action, body) {
    try {
        const response = await fetch(`api.php?resource=catalogo&action=${action}`, { method: 'POST', headers: { Accept: 'application/json' }, body });
        const data = await response.json();
        if (!response.ok || data.status !== 'success') throw new Error(data.message || 'No fue posible actualizar el catalogo.');
        await Swal.fire('Cambios guardados', data.message, 'success');
        location.reload();
    } catch (error) {
        Swal.fire('Error', error.message || 'No fue posible completar la operacion.', 'error');
    }
}
