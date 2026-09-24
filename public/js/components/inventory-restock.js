(function () {
    'use strict';

    function init() {
        const modalElement = document.getElementById('inventoryRestockModal');
        const dataElement = document.getElementById('inventoryRestockCatalogData');
        if (!modalElement || !dataElement || !window.bootstrap) return;

        const products = JSON.parse(dataElement.textContent || '[]');
        const catalog = new Map(products.map((product) => [`${product.branch_id}:${product.product_id}`, product]));
        const state = new Map();
        const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
        const form = document.getElementById('inventoryRestockForm');
        const lines = document.getElementById('restockLines');
        const empty = document.getElementById('restockEmptyState');
        const search = document.getElementById('restockProductSearch');
        const submit = document.getElementById('restockSubmit');
        const notes = document.getElementById('restockNotes');
        const unitCount = document.getElementById('restockUnitCount');
        const lineCount = document.getElementById('restockLineCount');
        const branchName = document.getElementById('restockBranchName');
        let activeBranch = Number(modalElement.dataset.defaultBranch || 0);
        let idempotencyKey = createKey();

        function createKey() {
            if (window.crypto?.randomUUID) return window.crypto.randomUUID();
            return `${Date.now()}-${Math.random().toString(16).slice(2)}-${Math.random().toString(16).slice(2)}`;
        }

        function suggestedQuantity(product) {
            return Math.max(Number(product.minimum) - Number(product.stock), 1);
        }

        function formatLocalDate(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }

        function suggestedExpiry(days) {
            const date = new Date();
            date.setHours(12, 0, 0, 0);
            date.setDate(date.getDate() + Number(days));
            return formatLocalDate(date);
        }

        function setBranch(branchId) {
            activeBranch = Number(branchId);
            const product = products.find((entry) => Number(entry.branch_id) === activeBranch);
            branchName.textContent = product?.branch_name || '';
            document.querySelectorAll('[data-restock-product]').forEach((button) => {
                button.hidden = Number(button.dataset.restockBranch) !== activeBranch;
            });
            search.value = '';
            filterCatalog();
        }

        function addProduct(key) {
            const product = catalog.get(key);
            if (!product) return;
            if (activeBranch && activeBranch !== Number(product.branch_id)) {
                state.clear();
            }
            setBranch(product.branch_id);
            if (!state.has(key)) {
                state.set(key, {
                    ...product,
                    quantity: suggestedQuantity(product),
                    expiry: Number(product.shelf_life) > 0 ? suggestedExpiry(product.shelf_life) : '',
                });
            }
            render();
        }

        function element(tag, className, text) {
            const node = document.createElement(tag);
            if (className) node.className = className;
            if (text !== undefined) node.textContent = text;
            return node;
        }

        function renderLine(key, item) {
            const article = element('article', 'inventory-restock__line');
            article.dataset.key = key;

            const heading = element('div', 'inventory-restock__line-heading');
            const copy = element('div', 'min-w-0');
            copy.append(element('strong', '', item.name));
            copy.append(element('small', '', `Disponible: ${item.stock} · Mínimo: ${item.minimum}`));
            const remove = element('button', 'btn inventory-restock__remove');
            remove.type = 'button';
            remove.dataset.restockAction = 'remove';
            remove.setAttribute('aria-label', `Quitar ${item.name}`);
            remove.innerHTML = '<i class="fa-solid fa-trash-can" aria-hidden="true"></i>';
            heading.append(copy, remove);

            const controls = element('div', 'inventory-restock__controls');
            const quantityGroup = element('div', 'inventory-restock__quantity-group');
            quantityGroup.append(element('label', '', 'Cantidad recibida'));
            const stepper = element('div', 'inventory-restock__stepper');
            const minus = element('button', '', '−');
            minus.type = 'button';
            minus.dataset.restockAction = 'decrease';
            minus.setAttribute('aria-label', 'Disminuir cantidad');
            const input = element('input');
            input.type = 'number';
            input.min = '1';
            input.max = '100000';
            input.step = '1';
            input.value = String(item.quantity);
            input.dataset.restockQuantity = '';
            input.setAttribute('aria-label', `Cantidad de ${item.name}`);
            const plus = element('button', '', '+');
            plus.type = 'button';
            plus.dataset.restockAction = 'increase';
            plus.setAttribute('aria-label', 'Aumentar cantidad');
            stepper.append(minus, input, plus);
            const quick = element('div', 'inventory-restock__quick');
            [5, 10, 20].forEach((amount) => {
                const button = element('button', '', `+${amount}`);
                button.type = 'button';
                button.dataset.restockAction = 'quick';
                button.dataset.amount = String(amount);
                quick.append(button);
            });
            quantityGroup.append(stepper, quick);
            controls.append(quantityGroup);

            if (Number(item.shelf_life) > 0) {
                const expiryGroup = element('div', 'inventory-restock__expiry-group');
                const label = element('label', '', 'Caducidad del lote');
                const expiry = element('input', 'form-control');
                expiry.type = 'date';
                expiry.min = formatLocalDate(new Date());
                expiry.value = item.expiry;
                expiry.required = true;
                expiry.dataset.appDate = '';
                expiry.dataset.restockExpiry = '';
                expiryGroup.append(label, expiry);
                controls.append(expiryGroup);
            }

            const result = element('div', 'inventory-restock__result');
            result.append(element('span', '', 'Stock después del ingreso'));
            result.append(element('strong', '', `${Number(item.stock) + Number(item.quantity)} unidades`));
            article.append(heading, controls, result);
            return article;
        }

        function render() {
            lines.replaceChildren();
            state.forEach((item, key) => lines.append(renderLine(key, item)));
            window.AppDatePicker?.scan(lines);
            const units = [...state.values()].reduce((total, item) => total + Number(item.quantity || 0), 0);
            const count = state.size;
            empty.hidden = count > 0;
            unitCount.textContent = String(units);
            lineCount.textContent = `${count} ${count === 1 ? 'producto' : 'productos'}`;
            submit.disabled = count === 0 || units < 1;
            document.querySelectorAll('[data-restock-product]').forEach((button) => {
                button.classList.toggle('is-added', state.has(button.dataset.restockProduct));
            });
        }

        function updateQuantity(line, next) {
            const item = state.get(line.dataset.key);
            if (!item) return;
            item.quantity = Math.max(1, Math.min(100000, Number(next) || 1));
            render();
        }

        function filterCatalog() {
            const term = search.value.trim().toLocaleLowerCase('es');
            let visible = 0;
            document.querySelectorAll('[data-restock-product]').forEach((button) => {
                const sameBranch = Number(button.dataset.restockBranch) === activeBranch;
                const matches = !term || (button.dataset.search || '').includes(term);
                button.hidden = !(sameBranch && matches);
                if (!button.hidden) visible += 1;
            });
            document.getElementById('restockCatalogEmpty').hidden = visible > 0;
        }

        document.addEventListener('click', (event) => {
            const openButton = event.target.closest('[data-restock-open], .btn-abastecer-directo');
            if (openButton) {
                event.preventDefault();
                const branchId = Number(openButton.dataset.sucursal || openButton.dataset.restockBranch || modalElement.dataset.defaultBranch || 0);
                if (branchId < 1) return;
                state.clear();
                idempotencyKey = createKey();
                notes.value = '';
                setBranch(branchId);
                const productId = Number(openButton.dataset.producto || 0);
                if (productId > 0) addProduct(`${branchId}:${productId}`);
                else render();
                const parentModal = openButton.closest('.modal.show');
                if (parentModal && parentModal !== modalElement) {
                    parentModal.addEventListener('hidden.bs.modal', () => modal.show(), { once: true });
                    bootstrap.Modal.getInstance(parentModal)?.hide();
                } else {
                    modal.show();
                }
                return;
            }

            const productButton = event.target.closest('[data-restock-product]');
            if (productButton) {
                addProduct(productButton.dataset.restockProduct);
                return;
            }

            const action = event.target.closest('[data-restock-action]');
            if (!action) return;
            const line = action.closest('.inventory-restock__line');
            const item = line && state.get(line.dataset.key);
            if (!line || !item) return;
            if (action.dataset.restockAction === 'remove') {
                state.delete(line.dataset.key);
                render();
            } else if (action.dataset.restockAction === 'decrease') {
                updateQuantity(line, Number(item.quantity) - 1);
            } else if (action.dataset.restockAction === 'increase') {
                updateQuantity(line, Number(item.quantity) + 1);
            } else if (action.dataset.restockAction === 'quick') {
                updateQuantity(line, Number(item.quantity) + Number(action.dataset.amount));
            }
        });

        lines.addEventListener('input', (event) => {
            const line = event.target.closest('.inventory-restock__line');
            const item = line && state.get(line.dataset.key);
            if (!item) return;
            if (event.target.matches('[data-restock-quantity]')) {
                item.quantity = Math.max(1, Math.min(100000, Number(event.target.value) || 1));
                const result = line.querySelector('.inventory-restock__result strong');
                if (result) result.textContent = `${Number(item.stock) + item.quantity} unidades`;
                unitCount.textContent = String([...state.values()].reduce((total, entry) => total + Number(entry.quantity), 0));
            } else if (event.target.matches('[data-restock-expiry]')) {
                item.expiry = event.target.value;
            }
        });
        search.addEventListener('input', filterCatalog);

        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            if (!form.reportValidity() || state.size === 0) return;
            submit.disabled = true;
            const original = submit.innerHTML;
            submit.innerHTML = '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span> Guardando...';
            const body = new FormData();
            body.append('action', 'abastecer');
            body.append('id_sucursal', String(activeBranch));
            body.append('idempotency_key', idempotencyKey);
            body.append('observaciones', notes.value.trim());
            body.append('items', JSON.stringify([...state.values()].map((item) => ({
                product_id: item.product_id,
                quantity: item.quantity,
                expiry: item.expiry || null,
            }))));

            try {
                const response = await fetch('api.php?resource=inventario&action=abastecer', {
                    method: 'POST',
                    headers: { Accept: 'application/json' },
                    body,
                });
                const data = await response.json();
                if (!response.ok || data.status !== 'success') throw new Error(data.message || 'No fue posible registrar el abastecimiento.');
                modal.hide();
                await Swal.fire({ icon: 'success', title: 'Inventario actualizado', text: data.message });
                location.reload();
            } catch (error) {
                await Swal.fire({ icon: 'error', title: 'No se pudo guardar', text: error.message });
                submit.disabled = false;
                submit.innerHTML = original;
            }
        });
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();
})();
