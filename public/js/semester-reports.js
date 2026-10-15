/* Reporting-period selectors refresh GET views only; write forms stay explicit. */
(() => {
    'use strict';

    document.querySelectorAll('form[data-report-period-form]').forEach((form) => {
        if (form.method.toLowerCase() !== 'get') return;
        form.querySelectorAll('select').forEach((select) => {
            select.addEventListener('change', () => form.requestSubmit());
        });
    });
})();
