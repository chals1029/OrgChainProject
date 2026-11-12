/* Reporting-period selectors refresh GET views only; write forms stay explicit. */
(() => {
    'use strict';

    document.addEventListener('change', (event) => {
        const select = event.target.closest('form[data-report-period-form] select');
        const form = select?.form;
        if (!form || form.method.toLowerCase() !== 'get') return;

        let targets, onAfterSwap;
        if (document.getElementById('srOrganizationDirectory')) {
            targets = ['#reportSubmissionControls', '#srDirectorySubtitle', '#srDirectoryCount', '#srDirectoryWrap', '#srDirectoryPagination'];
            onAfterSwap = () => window.refreshSrDirectory?.();
        } else if (document.getElementById('orgAccomplishmentRoot')) {
            targets = ['#orgAccomplishmentRoot', '#orgAccomplishmentSubtitle', '#orgAccomplishmentActions'];
            const sharedPreview = document.getElementById('sharedDocumentPreviewDialog');
            onAfterSwap = () => {
                const replacement = document.getElementById('sharedDocumentPreviewDialog');
                if (sharedPreview && replacement) replacement.replaceWith(sharedPreview);
                window.initOrgAccomplishment?.();
            };
        } else if (document.getElementById('soFinancialRoot')) {
            targets = ['#soFinancialRoot', '#soFinancialSubtitle', '#soFinancialActions'];
            const sharedPreview = document.getElementById('sharedDocumentPreviewDialog');
            onAfterSwap = () => {
                const replacement = document.getElementById('sharedDocumentPreviewDialog');
                if (sharedPreview && replacement) replacement.replaceWith(sharedPreview);
                window.initSoFinancial?.();
            };
        } else {
            form.requestSubmit();
            return;
        }
        if (!window.OrgSmoothFilter) { form.requestSubmit(); return; }
        window.OrgSmoothFilter.swap({ url: window.OrgSmoothFilter.buildUrl(form), targets, onAfterSwap });
    });
})();
