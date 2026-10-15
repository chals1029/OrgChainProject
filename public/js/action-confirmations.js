(function () {
    'use strict';

    const pending = new WeakSet();
    const mutatingMethods = new Set(['post', 'put', 'patch', 'delete']);

    function cancel(event) {
        event.preventDefault();
        event.stopImmediatePropagation();
        return false;
    }

    function confirmAction(message) {
        return window.confirm(message || 'Submit these changes?');
    }
    function methodFor(form, submitter) {
        return (submitter?.hasAttribute('formmethod') ? submitter.formMethod : form.method).toLowerCase();
    }


    function messageFor(form, submitter) {
        const explicit = submitter?.getAttribute('data-confirm-message') || form.getAttribute('data-confirm-message');
        if (explicit) return explicit;
        const label = (submitter?.getAttribute('aria-label') || submitter?.textContent || submitter?.value || '')
            .replace(/\s+/g, ' ').trim();
        return label ? `${label.replace(/[?.!]+$/, '')}?` : 'Submit these changes?';
    }

    // Manual AJAX/custom-validation submit handlers call this once, after validation,
    // on a data-confirm="manual" form. No resubmission: submitter overrides and values
    // remain intact. AJAX handlers retain their existing in-flight guards.
    function confirmSubmit(event, message) {
        const form = event.target;
        if (!(form instanceof HTMLFormElement)) return false;
        const submitter = event.submitter;
        if (!mutatingMethods.has(methodFor(form, submitter))) return true;
        if (pending.has(form)) return cancel(event);
        if (!form.noValidate && !submitter?.formNoValidate && !form.reportValidity()) return cancel(event);
        if (!confirmAction(message || messageFor(form, submitter))) return cancel(event);
        pending.add(form);
        setTimeout(() => {
            // AJAX/validation handlers own their lifecycle; a native navigation stays
            // locked against repeated clicks until the next page or bfcache restore.
            if (event.defaultPrevented) pending.delete(form);
        }, 0);
        return true;
    }

    // Capture before inline and delegated AJAX handlers can produce side effects.
    // GET/filter/dialog submits and explicitly harmless forms never prompt.
    window.addEventListener('submit', (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || event.defaultPrevented) return;
        const submitter = event.submitter;
        const mode = submitter?.getAttribute('data-confirm') || form.getAttribute('data-confirm');
        if (mode === 'off' || mode === 'manual') return;
        if (!mutatingMethods.has(methodFor(form, submitter))) return;
        confirmSubmit(event);
    }, true);

    window.addEventListener('pageshow', () => {
        document.querySelectorAll('form').forEach((form) => pending.delete(form));
    });

    // data-confirm-message: action-specific text on form or submitter.
    // data-confirm="off": harmless mutation/preview, or an existing dedicated modal.
    // data-confirm="manual": handler invokes confirmSubmit after its custom checks.
    // Non-form mutations call confirm(message) before changing UI or sending requests.
    window.OrgActionConfirmations = Object.freeze({ confirm: confirmAction, confirmSubmit });
})();
