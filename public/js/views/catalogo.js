document.addEventListener('DOMContentLoaded', () => {
    const productForm = document.getElementById('productForm');
    const productModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('productModal'));
    document.querySelectorAll('.js-edit-product').forEach((button) => button.addEventListener('click', () => {
        const product = JSON.parse(button.dataset.product);
        document.getElementById('productId').value = product.id_producto;
        document.getElementById('productName').value = product.nombre_producto;
        document.getElementById('productCategory').value = product.id_categoria;
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
