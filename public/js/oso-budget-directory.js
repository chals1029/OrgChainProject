(function () {
    'use strict';

    document.querySelectorAll('[data-budget-directory]').forEach(function (root) {
        var form = root.querySelector('[data-directory-form]');
        var input = root.querySelector('[data-directory-query]');
        var count = root.querySelector('[data-directory-count]');
        var noMatch = root.querySelector('[data-directory-no-match]');
        var rows = Array.from(root.querySelectorAll('[data-directory-row]')).map(function (row) {
            return { element: row, text: (row.dataset.searchValue || '').toLowerCase() };
        });
        var label = root.dataset.budgetDirectory === 'activities' ? 'approved activities' : 'organizations';
        var pagination = root.querySelector('[data-directory-pagination]');
        var pageInfo = root.querySelector('[data-directory-page-info]');
        var pageNav = root.querySelector('[data-directory-pages]');
        var pageSize = pagination ? 5 : rows.length || 1;
        var page = 1;

        function pageButton(text, target, disabled) {
            var button = document.createElement('button');
            button.type = 'button';
            button.className = 'org-report-page-btn';
            button.textContent = text;
            button.disabled = disabled;
            button.addEventListener('click', function () {
                page = target;
                render();
            });
            return button;
        }

        function render() {
            var query = input.value.trim().toLowerCase();
            var matches = rows.filter(function (row) { return row.text.includes(query); });
            var totalPages = Math.max(1, Math.ceil(matches.length / pageSize));
            page = Math.min(page, totalPages);
            var start = (page - 1) * pageSize;
            rows.forEach(function (row) { row.element.hidden = true; });
            matches.slice(start, start + pageSize).forEach(function (row) { row.element.hidden = false; });
            count.textContent = matches.length + ' of ' + rows.length + ' ' + label;
            noMatch.hidden = rows.length === 0 || matches.length > 0;
            if (!pagination) return;
            pagination.hidden = matches.length === 0;
            pageInfo.textContent = matches.length
                ? 'Showing ' + (start + 1) + '–' + Math.min(start + pageSize, matches.length) + ' of ' + matches.length + ' ' + label
                : '';
            var current = document.createElement('span');
            current.textContent = 'Page ' + page + ' of ' + totalPages;
            current.setAttribute('aria-live', 'polite');
            pageNav.replaceChildren(
                pageButton('Previous', page - 1, page === 1),
                current,
                pageButton('Next', page + 1, page === totalPages)
            );
        }

        function search() {
            page = 1;
            render();
        }

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            search();
        });
        input.addEventListener('input', search);
        search();
    });

    var dialog = document.getElementById('osoBudgetDetailsDialog');
    if (!dialog || typeof dialog.showModal !== 'function') return;
    var body = dialog.querySelector('[data-budget-details-body]');
    var title = document.getElementById('osoBudgetDetailsTitle');
    var college = dialog.querySelector('[data-budget-details-college]');
    var opener = null;
    var kicker = dialog.querySelector('[data-budget-details-kicker]');
    var footnote = dialog.querySelector('[data-budget-details-footnote]');
    var activityOpener = null;
    var listScroll = 0;
    var receiptEntries = [];

    function resetActivity() {
        var panel = body.querySelector('[data-budget-detail-panel]');
        if (panel) panel.remove();
        var list = body.querySelector('.oso-budget-details-activities');
        if (list) list.hidden = false;
        kicker.textContent = 'Organization activities';
        footnote.textContent = 'Activities shown belong only to this organization.';
        receiptEntries = [];
    }

    body.addEventListener('click', function (event) {
        if (event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
        var back = event.target.closest('[data-budget-activities-back]');
        if (back) {
            resetActivity();
            body.scrollTop = listScroll;
            if (activityOpener && activityOpener.isConnected) activityOpener.focus({ preventScroll: true });
            activityOpener = null;
            return;
        }
        var receipt = event.target.closest('[data-budget-dialog-receipt]');
        if (receipt) {
            var expense = receiptEntries.find(function (entry) { return String(entry.id) === receipt.dataset.budgetDialogReceipt; });
            if (!expense || typeof window.openBudgetReceiptPreview !== 'function') return;
            event.preventDefault();
            window.openBudgetReceiptPreview(expense, receipt);
            return;
        }
        var activity = event.target.closest('[data-budget-details-activity]');
        if (!activity) return;
        var template = document.getElementById('osoBudgetActivity' + activity.dataset.budgetDetailsActivity);
        if (!template) return;
        event.preventDefault();
        listScroll = body.scrollTop;
        activityOpener = activity;
        var panel = document.createElement('div');
        panel.setAttribute('data-budget-detail-panel', '');
        panel.appendChild(template.content.cloneNode(true));
        receiptEntries = JSON.parse(panel.querySelector('[data-budget-detail-receipts]').textContent);
        body.querySelector('.oso-budget-details-activities').hidden = true;
        body.appendChild(panel);
        kicker.textContent = 'Activity expense details';
        footnote.textContent = 'Receipts open in a read-only preview.';
        body.scrollTop = 0;
        panel.querySelector('[data-budget-activity-title]').focus({ preventScroll: true });
    });
    body.addEventListener('input', function (event) {
        if (!event.target.matches('[data-budget-expense-search]')) return;
        var detail = event.target.closest('[data-budget-activity-detail]');
        var query = event.target.value.trim().toLowerCase();
        var rows = Array.from(detail.querySelectorAll('[data-budget-expense-row]'));
        var matches = 0;
        rows.forEach(function (row) {
            row.hidden = !(row.dataset.searchValue || '').toLowerCase().includes(query);
            if (!row.hidden) matches++;
        });
        detail.querySelector('[data-budget-expense-empty]').hidden = rows.length === 0 || matches > 0;
        detail.querySelector('[data-budget-expense-count]').textContent = matches + ' of ' + rows.length + ' encoded items';
    });

    document.querySelectorAll('[data-budget-organization-details]').forEach(function (link) {
        link.addEventListener('click', function (event) {
            if (event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
            var template = document.getElementById(link.dataset.budgetOrganizationDetails);
            if (!template) return;
            event.preventDefault();
            opener = link;
            title.textContent = link.dataset.organizationName;
            college.textContent = link.dataset.organizationCollege;
            body.replaceChildren(template.content.cloneNode(true));
            body.scrollTop = 0;
            resetActivity();
            activityOpener = null;
            listScroll = 0;
            dialog.showModal();
            document.body.classList.add('oso-budget-details-open');
            title.focus({ preventScroll: true });
        });
    });
    dialog.querySelectorAll('[data-budget-details-close]').forEach(function (button) {
        button.addEventListener('click', function () { dialog.close(); });
    });
    dialog.addEventListener('click', function (event) {
        if (event.target !== dialog) return;
        var bounds = dialog.getBoundingClientRect();
        if (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom) dialog.close();
    });
    dialog.addEventListener('close', function () {
        document.body.classList.remove('oso-budget-details-open');
        body.replaceChildren();
        activityOpener = null;
        receiptEntries = [];
        if (opener && opener.isConnected) opener.focus({ preventScroll: true });
        opener = null;
    });
})();
