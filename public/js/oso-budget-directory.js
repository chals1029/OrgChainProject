(function () {
    'use strict';

    document.querySelectorAll('[data-budget-directory]').forEach(function (root) {
        var form = root.querySelector('[data-directory-form]');
        var input = root.querySelector('[data-directory-query]');
        var clear = root.querySelector('[data-directory-clear]');
        var count = root.querySelector('[data-directory-count]');
        var noMatch = root.querySelector('[data-directory-no-match]');
        var rows = Array.from(root.querySelectorAll('[data-directory-row]')).map(function (row) {
            return { element: row, text: (row.dataset.searchValue || '').toLowerCase() };
        });
        var label = root.dataset.budgetDirectory === 'activities' ? 'approved activities' : 'organizations';

        function search() {
            var query = input.value.trim().toLowerCase();
            var matches = 0;
            rows.forEach(function (row) {
                var matched = row.text.includes(query);
                row.element.hidden = !matched;
                if (matched) matches += 1;
            });
            count.textContent = matches + ' of ' + rows.length + ' ' + label;
            noMatch.hidden = rows.length === 0 || matches > 0;
        }

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            search();
        });
        input.addEventListener('input', search);
        clear.addEventListener('click', function () {
            input.value = '';
            search();
            input.focus();
        });
        search();
    });
})();
