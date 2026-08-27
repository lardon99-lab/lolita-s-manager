(function () {
    'use strict';
    const token = document.querySelector('meta[name="csrf-token"]')?.content || '';

    function configureDialogs() {
        if (!window.Swal || window.Swal.__lolitasConfigured) return;

        const originalFire = window.Swal.fire.bind(window.Swal);
        window.Swal.fire = function (...args) {
            if (typeof args[0] === 'object' && args[0] !== null) {
                const options = args[0];
                const customClass = options.customClass || {};
                args[0] = {
                    ...options,
                    buttonsStyling: false,
                    customClass: {
                        ...customClass,
                        popup: 'app-dialog',
                        title: 'app-dialog__title',
                        htmlContainer: 'app-dialog__body',
                        actions: 'app-dialog__actions',
                        confirmButton: `btn app-dialog__button app-dialog__button--primary ${customClass.confirmButton || ''}`,
                        denyButton: `btn app-dialog__button app-dialog__button--secondary ${customClass.denyButton || ''}`,
                        cancelButton: `btn app-dialog__button app-dialog__button--ghost ${customClass.cancelButton || ''}`,
                        input: `form-control app-dialog__input ${customClass.input || ''}`
                    }
                };
            } else {
                const [title, text, icon] = args;
                args = [{
                    title,
                    text,
                    icon,
                    buttonsStyling: false,
                    customClass: {
                        popup: 'app-dialog',
                        title: 'app-dialog__title',
                        htmlContainer: 'app-dialog__body',
                        actions: 'app-dialog__actions',
                        confirmButton: 'btn app-dialog__button app-dialog__button--primary'
                    }
                }];
            }

            return originalFire(...args);
        };
        window.Swal.__lolitasConfigured = true;
    }

    configureDialogs();
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('form').forEach(function (form) {
            const method = (form.getAttribute('method') || 'GET').toUpperCase();
            if (method === 'GET' || form.querySelector('input[name="_csrf"]')) return;
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = '_csrf';
            input.value = token;
            form.appendChild(input);
        });
    });
    const nativeFetch = window.fetch;
    window.fetch = function (resource, options) {
        const requestOptions = Object.assign({}, options || {});
        const url = typeof resource === 'string' ? new URL(resource, window.location.href) : new URL(resource.url);
        if (url.origin === window.location.origin && (requestOptions.method || 'GET').toUpperCase() !== 'GET') {
            const headers = new Headers(requestOptions.headers || {});
            headers.set('X-CSRF-Token', token);
            requestOptions.headers = headers;
        }
        return nativeFetch.call(window, resource, requestOptions);
    };
})();
