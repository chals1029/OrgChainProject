(function () {
    'use strict';

    const mutatingMethods = new Set(['post', 'put', 'patch', 'delete']);
    const pending = new WeakSet();
    const prompting = new WeakSet();
    const approved = new WeakMap();
    const explicitOnly = document.currentScript?.dataset.confirmScope === 'explicit';
    let dialog;
    let resolveDialog;

    function cancel(event) {
        event.preventDefault();
        event.stopImmediatePropagation();
        return false;
    }

    function createDialog() {
        const element = document.createElement('dialog');
        element.className = 'action-confirm-dialog';
        element.setAttribute('aria-labelledby', 'actionConfirmTitle');
        element.setAttribute('aria-describedby', 'actionConfirmMessage');
        element.innerHTML = '<div class="action-confirm-box">' +
            '<span class="action-confirm-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.3 3.9a2 2 0 0 1 3.4 0l8 13.8a2 2 0 0 1-1.7 3H4a2 2 0 0 1-1.7-3z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg></span>' +
            '<h2 id="actionConfirmTitle"></h2><p id="actionConfirmMessage"></p>' +
            '<div class="action-confirm-actions"><button type="button" class="action-confirm-cancel">Cancel</button><button type="button" class="action-confirm-accept"></button></div></div>';
        document.body.appendChild(element);
        element.querySelector('.action-confirm-cancel').addEventListener('click', () => element.close('cancel'));
        element.querySelector('.action-confirm-accept').addEventListener('click', () => element.close('confirm'));
        element.addEventListener('cancel', (event) => {
            event.preventDefault();
            element.close('cancel');
        });
        element.addEventListener('click', (event) => {
            if (event.target !== element) return;
            const bounds = element.getBoundingClientRect();
            if (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom) {
                element.close('cancel');
            }
        });
        element.addEventListener('close', () => {
            const resolve = resolveDialog;
            resolveDialog = null;
            if (resolve) resolve(element.returnValue === 'confirm');
        });
        return element;
    }

    function actionLabel(message) {
        const match = String(message).match(/\b(remove|delete|reset|clear|disable|activate|reject|return|approve|accept|publish|unpublish|submit|save|create|replace|upload|send|lock|reopen|archive|log out)\b/i);
        if (!match) return 'Confirm';
        const word = match[1].toLowerCase();
        return word.charAt(0).toUpperCase() + word.slice(1);
    }

    function confirmAction(message, options = {}) {
        if (resolveDialog) return Promise.resolve(false);
        const text = String(message || 'Submit these changes?');
        const action = actionLabel(text);
        if (!dialog) dialog = createDialog();
        dialog.querySelector('#actionConfirmTitle').textContent = options.title || (action === 'Confirm' ? 'Confirm action?' : `${action}?`);
        dialog.querySelector('#actionConfirmMessage').textContent = text;
        dialog.querySelector('.action-confirm-accept').textContent = options.accept || (action === 'Confirm' ? 'Confirm' : `Yes, ${action}`);
        dialog.returnValue = '';
        return new Promise((resolve) => {
            resolveDialog = resolve;
            try {
                dialog.showModal();
                dialog.querySelector('.action-confirm-cancel').focus();
            } catch (_) {
                resolveDialog = null;
                resolve(false);
            }
        });
    }

    function methodFor(form, submitter) {
        return (submitter?.hasAttribute('formmethod') ? submitter.formMethod : form.method).toLowerCase();
    }

    function attributeFor(form, submitter, name) {
        return submitter?.getAttribute(name) || form.getAttribute(name);
    }

    function messageFor(form, submitter) {
        const explicit = attributeFor(form, submitter, 'data-confirm-message');
        if (explicit) return explicit;
        const label = (submitter?.getAttribute('aria-label') || submitter?.textContent || submitter?.value || '')
            .replace(/\s+/g, ' ').trim();
        return label ? `${label.replace(/[?.!]+$/, '')}?` : 'Submit these changes?';
    }

    // Manual handlers call this after their validation. Approval replays the submit
    // so the original submitter, native validation and all handlers run normally.
    function confirmSubmit(event, message) {
        const form = event.target;
        if (!(form instanceof HTMLFormElement)) return false;
        const submitter = event.submitter || null;
        if (!mutatingMethods.has(methodFor(form, submitter))) return true;
        if (approved.has(form) && approved.get(form) === submitter) {
            approved.delete(form);
            if (pending.has(form)) return cancel(event);
            pending.add(form);
            setTimeout(() => {
                if (event.defaultPrevented) pending.delete(form);
            }, 0);
            return true;
        }
        if (pending.has(form) || prompting.has(form)) return cancel(event);
        if (!form.noValidate && !submitter?.formNoValidate && !form.reportValidity()) return cancel(event);
        cancel(event);
        prompting.add(form);
        const options = {
            title: attributeFor(form, submitter, 'data-confirm-title'),
            accept: attributeFor(form, submitter, 'data-confirm-accept'),
        };
        confirmAction(message || messageFor(form, submitter), options).then((confirmed) => {
            prompting.delete(form);
            if (!confirmed || !form.isConnected || (submitter && (!submitter.isConnected || submitter.form !== form || submitter.disabled))) return;
            approved.set(form, submitter);
            try {
                if (submitter) form.requestSubmit(submitter);
                else form.requestSubmit();
            } catch (_) {
                // A changed or removed submitter must not bypass validation.
            } finally {
                approved.delete(form);
            }
        });
        return false;
    }

    // Capture before inline and delegated handlers can produce side effects.
    window.addEventListener('submit', (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || event.defaultPrevented) return;
        const submitter = event.submitter;
        const mode = attributeFor(form, submitter, 'data-confirm');
        if (mode === 'off' || mode === 'manual') return;
        if (explicitOnly && mode !== 'auto' && !attributeFor(form, submitter, 'data-confirm-message')) return;
        if (!mutatingMethods.has(methodFor(form, submitter))) return;
        confirmSubmit(event);
    }, true);

    window.addEventListener('pageshow', () => {
        document.querySelectorAll('form').forEach((form) => pending.delete(form));
    });

    window.OrgActionConfirmations = Object.freeze({ confirm: confirmAction, confirmSubmit });
})();
