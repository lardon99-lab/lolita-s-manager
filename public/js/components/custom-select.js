(function () {
    'use strict';

    const instances = new WeakMap();
    let openInstance = null;

    function eligible(select) {
        return select instanceof HTMLSelectElement
            && !select.multiple
            && Number(select.getAttribute('size') || 1) <= 1
            && select.dataset.nativeSelect !== 'true'
            && !select.matches('.swal2-select')
            && !select.closest('.swal2-container');
    }

    function enhance(select) {
        if (instances.has(select) || !eligible(select)) return;
        const wrapper = document.createElement('div');
        wrapper.className = 'app-select';
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'app-select__trigger';
        button.setAttribute('role', 'combobox');
        button.setAttribute('aria-haspopup', 'listbox');
        button.setAttribute('aria-expanded', 'false');
        const label = select.id ? document.querySelector(`label[for="${CSS.escape(select.id)}"]`) : null;
        button.setAttribute('aria-label', select.getAttribute('aria-label') || label?.textContent.trim() || select.name || 'Seleccionar');
        const value = document.createElement('span');
        value.className = 'app-select__value';
        const arrow = document.createElement('i');
        arrow.className = 'fa-solid fa-chevron-down app-select__arrow';
        arrow.setAttribute('aria-hidden', 'true');
        button.append(value, arrow);
        const menu = document.createElement('div');
        menu.className = 'app-select__menu';
        menu.setAttribute('role', 'listbox');
        wrapper.append(button, menu);
        select.insertAdjacentElement('afterend', wrapper);
        select.classList.add('app-select__native');

        const instance = { select, wrapper, button, value, menu, activeIndex: -1 };
        instances.set(select, instance);
        button.addEventListener('click', () => toggle(instance));
        button.addEventListener('keydown', (event) => onKeyDown(event, instance));
        select.addEventListener('change', () => sync(select));
        select.addEventListener('app-select-sync', () => sync(select));
        select.addEventListener('focus', () => button.focus());
        render(instance);
    }

    function destroy(select) {
        const instance = instances.get(select);
        if (!instance) return;
        if (openInstance === instance) close(instance);
        instance.wrapper.remove();
        select.classList.remove('app-select__native');
        instances.delete(select);
    }

    function render(instance) {
        const { select, menu } = instance;
        menu.replaceChildren();
        const options = [...select.options].filter((option) => !option.hidden && option.style.display !== 'none');
        options.forEach((option, index) => {
            const item = document.createElement('button');
            item.type = 'button';
            item.className = 'app-select__option';
            item.setAttribute('role', 'option');
            item.dataset.index = String(option.index);
            item.disabled = option.disabled;
            item.textContent = option.textContent.trim();
            item.setAttribute('aria-selected', option.selected ? 'true' : 'false');
            if (option.selected) {
                item.classList.add('is-selected');
                instance.activeIndex = index;
            }
            item.addEventListener('click', () => selectOption(instance, option.index));
            menu.appendChild(item);
        });
        sync(select, false);
    }

    function sync(select, rerender = true) {
        const instance = instances.get(select);
        if (!instance) return;
        if (!eligible(select)) {
            destroy(select);
            return;
        }
        if (rerender) {
            render(instance);
            return;
        }
        const selected = select.selectedOptions[0];
        instance.value.textContent = selected?.textContent.trim() || 'Seleccionar';
        instance.button.disabled = select.disabled;
        instance.button.classList.toggle('is-placeholder', !selected || selected.value === '');
        instance.menu.querySelectorAll('.app-select__option').forEach((item) => {
            const current = Number(item.dataset.index) === select.selectedIndex;
            item.classList.toggle('is-selected', current);
            item.setAttribute('aria-selected', current ? 'true' : 'false');
        });
    }

    function selectOption(instance, optionIndex) {
        const option = instance.select.options[optionIndex];
        if (!option || option.disabled) return;
        instance.select.value = option.value;
        instance.select.dispatchEvent(new Event('change', { bubbles: true }));
        close(instance);
        instance.button.focus();
    }

    function toggle(instance) {
        if (instance.button.disabled) return;
        if (openInstance === instance) {
            close(instance);
            return;
        }
        if (openInstance) close(openInstance);
        render(instance);
        const rect = instance.button.getBoundingClientRect();
        const spaceBelow = window.innerHeight - rect.bottom;
        const openUpward = spaceBelow < 250 && rect.top > spaceBelow;
        const menuWidth = Math.min(rect.width, window.innerWidth - 16);
        const menuLeft = Math.min(Math.max(8, rect.left), window.innerWidth - menuWidth - 8);
        document.body.appendChild(instance.menu);
        instance.menu.style.position = 'fixed';
        instance.menu.style.right = 'auto';
        instance.menu.style.left = `${menuLeft}px`;
        instance.menu.style.width = `${menuWidth}px`;
        instance.menu.style.top = openUpward ? 'auto' : `${rect.bottom + 6}px`;
        instance.menu.style.bottom = openUpward ? `${window.innerHeight - rect.top + 6}px` : 'auto';
        instance.menu.classList.add('is-open');
        instance.wrapper.classList.add('is-open');
        instance.button.setAttribute('aria-expanded', 'true');
        openInstance = instance;
        const selected = instance.menu.querySelector('.is-selected:not(:disabled)') || instance.menu.querySelector('.app-select__option:not(:disabled)');
        selected?.scrollIntoView({ block: 'nearest' });
    }

    function close(instance) {
        instance.menu.classList.remove('is-open');
        instance.menu.removeAttribute('style');
        instance.wrapper.appendChild(instance.menu);
        instance.wrapper.classList.remove('is-open');
        instance.button.setAttribute('aria-expanded', 'false');
        if (openInstance === instance) openInstance = null;
    }

    function onKeyDown(event, instance) {
        const items = [...instance.menu.querySelectorAll('.app-select__option:not(:disabled)')];
        if (['ArrowDown', 'ArrowUp', 'Home', 'End'].includes(event.key)) {
            event.preventDefault();
            if (openInstance !== instance) toggle(instance);
            let index = items.findIndex((item) => item === document.activeElement || item.classList.contains('is-active'));
            items.forEach((item) => item.classList.remove('is-active'));
            if (event.key === 'Home') index = 0;
            else if (event.key === 'End') index = items.length - 1;
            else index = Math.max(0, Math.min(items.length - 1, index + (event.key === 'ArrowDown' ? 1 : -1)));
            items[index]?.classList.add('is-active');
            items[index]?.scrollIntoView({ block: 'nearest' });
        } else if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            if (openInstance !== instance) toggle(instance);
            else {
                const active = instance.menu.querySelector('.is-active') || instance.menu.querySelector('.is-selected');
                if (active) selectOption(instance, Number(active.dataset.index));
            }
        } else if (event.key === 'Escape' && openInstance === instance) {
            event.preventDefault();
            close(instance);
        }
    }

    function isInsideOpenSelect(target) {
        return openInstance
            && target instanceof Node
            && (openInstance.wrapper.contains(target) || openInstance.menu.contains(target));
    }

    function scan(root = document) {
        const selects = [];
        if (root instanceof HTMLSelectElement) selects.push(root);
        root.querySelectorAll?.('select').forEach((select) => selects.push(select));
        selects.forEach((select) => {
            if (eligible(select)) {
                if (instances.has(select)) sync(select);
                else enhance(select);
            }
            else destroy(select);
        });
    }

    document.addEventListener('click', (event) => {
        if (openInstance && !isInsideOpenSelect(event.target)) close(openInstance);
    });
    document.addEventListener('shown.bs.modal', () => scan());
    document.addEventListener('reset', (event) => setTimeout(() => scan(event.target), 0));
    window.addEventListener('resize', () => { if (openInstance) close(openInstance); });
    window.addEventListener('scroll', (event) => {
        if (openInstance && !isInsideOpenSelect(event.target)) close(openInstance);
    }, true);

    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            if (mutation.type === 'childList') {
                mutation.addedNodes.forEach((node) => { if (node.nodeType === Node.ELEMENT_NODE) scan(node); });
                if (mutation.target instanceof HTMLSelectElement) sync(mutation.target);
            } else if (mutation.target instanceof HTMLSelectElement) {
                if (eligible(mutation.target)) {
                    if (instances.has(mutation.target)) sync(mutation.target);
                    else enhance(mutation.target);
                }
                else destroy(mutation.target);
            }
        });
    });

    document.addEventListener('DOMContentLoaded', () => {
        scan();
        observer.observe(document.body, { subtree: true, childList: true, attributes: true, attributeFilter: ['multiple', 'size', 'disabled'] });
    });

    window.AppSelect = { scan, sync };
})();
