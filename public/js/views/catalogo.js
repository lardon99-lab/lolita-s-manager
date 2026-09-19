document.addEventListener('DOMContentLoaded', () => {
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
});

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
