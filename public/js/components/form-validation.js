(function () {
    'use strict';

    const fieldSelector = 'input:not([type="hidden"]), select, textarea';

    function feedbackFor(field) {
        const id = field.id || `${field.name || 'field'}-${Math.random().toString(36).slice(2)}`;
        field.id = id;
        let feedback = field.parentElement?.querySelector(`.invalid-feedback[data-for="${CSS.escape(id)}"]`);
        if (!feedback) {
            feedback = document.createElement('div');
            feedback.className = 'invalid-feedback';
            feedback.dataset.for = id;
            (field.closest('.input-group') || field).insertAdjacentElement('afterend', feedback);
        }
        feedback.id = `${id}-error`;
        return feedback;
    }

    function clearField(field) {
        field.classList.remove('is-invalid');
        field.removeAttribute('aria-invalid');
        const describedBy = (field.getAttribute('aria-describedby') || '')
            .split(/\s+/).filter((value) => value && value !== `${field.id}-error`);
        if (describedBy.length) field.setAttribute('aria-describedby', describedBy.join(' '));
        else field.removeAttribute('aria-describedby');
        const feedback = field.form?.querySelector(`.invalid-feedback[data-for="${CSS.escape(field.id || '')}"]`);
        if (feedback) feedback.textContent = '';
    }

    function invalidate(field, message) {
        const feedback = feedbackFor(field);
        feedback.textContent = message || field.validationMessage || 'Revisa este campo.';
        field.classList.add('is-invalid');
        field.setAttribute('aria-invalid', 'true');
        const describedBy = new Set((field.getAttribute('aria-describedby') || '').split(/\s+/).filter(Boolean));
        describedBy.add(feedback.id);
        field.setAttribute('aria-describedby', Array.from(describedBy).join(' '));
    }

    function clear(form) {
        form.querySelectorAll(fieldSelector).forEach(clearField);
        form.querySelector('[data-form-error-summary]')?.remove();
    }

    function applyErrors(form, errors) {
        clear(form);
        let first = null;
        const formMessages = [];
        Object.entries(errors || {}).forEach(([name, message]) => {
            const field = form.elements.namedItem(name);
            const target = field instanceof RadioNodeList ? field[0] : field;
            if (!(target instanceof HTMLElement)) {
                formMessages.push(String(message));
                return;
            }
            invalidate(target, String(message));
            first ||= target;
        });
        if (formMessages.length) {
            const summary = document.createElement('div');
            summary.className = 'alert alert-danger small';
            summary.dataset.formErrorSummary = '';
            summary.setAttribute('role', 'alert');
            summary.textContent = formMessages.join(' ');
            form.prepend(summary);
        }
        first?.focus({ preventScroll: true });
        first?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    function setSubmitting(form, submitting) {
        form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach((button) => {
            button.disabled = submitting;
            button.setAttribute('aria-busy', submitting ? 'true' : 'false');
        });
    }

    document.addEventListener('invalid', (event) => {
        const field = event.target;
        if (!(field instanceof HTMLInputElement || field instanceof HTMLSelectElement || field instanceof HTMLTextAreaElement)) return;
        invalidate(field, field.validationMessage);
    }, true);

    document.addEventListener('input', (event) => {
        const field = event.target;
        if (field instanceof HTMLInputElement || field instanceof HTMLTextAreaElement) clearField(field);
    });
    document.addEventListener('change', (event) => {
        if (event.target instanceof HTMLSelectElement) clearField(event.target);
    });
    document.addEventListener('hidden.bs.modal', (event) => {
        event.target.querySelectorAll('form').forEach(clear);
    });

    window.AppFormValidation = { applyErrors, clear, setSubmitting, invalidate };
})();
