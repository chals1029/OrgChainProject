(function () {
    'use strict';
    var dialog = document.querySelector('[data-tosa-workbook-dialog]');
    if (!dialog) return;
    var tabs = dialog.querySelector('[data-workbook-tabs]');
    var grid = dialog.querySelector('[data-workbook-grid]');
    var status = dialog.querySelector('[data-workbook-status]');
    var download = dialog.querySelector('[data-workbook-download]');
    var buttons = Array.from(dialog.querySelectorAll('[data-workbook-page]'));
    var opener = null, endpoint = '', controller = null, generation = 0, current = null;

    function columnName(value) {
        var name = '';
        for (var n = value; n > 0; n = Math.floor((n - 1) / 26)) name = String.fromCharCode(65 + ((n - 1) % 26)) + name;
        return name;
    }
    function cell(tag, text) {
        var node = document.createElement(tag);
        node.textContent = text;
        return node;
    }
    function setButtons(disabled) {
        buttons.forEach(function (button) { button.disabled = disabled; });
    }
    function close() {
        var previous = opener;
        opener = null;
        generation++;
        if (controller) controller.abort();
        controller = null;
        current = null;
        if (dialog.open) dialog.close();
        if (previous && previous.isConnected) previous.focus();
    }
    function render(data, focusTab) {
        current = data;
        if (data.download_url) {
            var original = new URL(data.download_url, window.location.href);
            if (original.origin === window.location.origin) download.href = original.href;
        }
        tabs.replaceChildren();
        data.sheets.forEach(function (name, index) {
            var button = cell('button', name);
            button.type = 'button';
            button.id = 'tosaWorkbookTab' + index;
            button.setAttribute('role', 'tab');
            button.setAttribute('aria-controls', 'tosaWorkbookSheet');
            button.addEventListener('click', function () { load(index, 0, 0, true); });
            button.setAttribute('aria-selected', index === data.sheet ? 'true' : 'false');
            button.tabIndex = index === data.sheet ? 0 : -1;
            button.addEventListener('keydown', function (event) {
                var targets = { ArrowRight: index + 1, ArrowLeft: index - 1, Home: 0, End: data.sheets.length - 1 };
                if (!(event.key in targets)) return;
                event.preventDefault();
                load((targets[event.key] + data.sheets.length) % data.sheets.length, 0, 0, true);
            });
            tabs.append(button);
        });
        grid.setAttribute('aria-labelledby', 'tosaWorkbookTab' + data.sheet);
        var table = document.createElement('table');
        var header = document.createElement('thead');
        var headerRow = document.createElement('tr');
        headerRow.append(cell('th', ''));
        for (var col = data.first_column; col <= data.last_column; col++) {
            var heading = cell('th', columnName(col));
            heading.scope = 'col';
            headerRow.append(heading);
        }
        header.append(headerRow);
        table.append(header);
        var body = document.createElement('tbody');
        data.rows.forEach(function (row, index) {
            var tr = document.createElement('tr');
            var rowHeading = cell('th', String(data.first_row + index));
            rowHeading.scope = 'row';
            tr.append(rowHeading);
            row.forEach(function (item) {
                if (item.skip) return;
                var td = cell('td', item.text == null ? '' : String(item.text));
                if (item.colspan > 1) td.colSpan = item.colspan;
                if (item.rowspan > 1) td.rowSpan = item.rowspan;
                tr.append(td);
            });
            body.append(tr);
        });
        table.append(body);
        grid.replaceChildren(table);
        grid.scrollTop = 0;
        grid.scrollLeft = 0;
        status.textContent = 'Rows ' + data.first_row + '–' + data.last_row + ' of ' + data.total_rows
            + ' · Columns ' + columnName(data.first_column) + '–' + columnName(data.last_column) + ' of ' + columnName(data.total_columns) + '.';
        buttons.forEach(function (button) {
            var action = button.dataset.workbookPage;
            button.disabled = action === 'row-prev' ? data.row_page === 0
                : action === 'row-next' ? data.row_page + 1 >= data.row_pages
                : action === 'column-prev' ? data.column_page === 0
                : data.column_page + 1 >= data.column_pages;
        });
        if (focusTab && tabs.children[data.sheet]) tabs.children[data.sheet].focus();
    }
    async function load(sheet, row, column, focusTab) {
        generation++;
        var token = generation;
        if (controller) controller.abort();
        controller = new AbortController();
        status.textContent = 'Loading worksheet…';
        grid.setAttribute('aria-busy', 'true');
        setButtons(true);
        try {
            var url = new URL(endpoint, window.location.href);
            url.searchParams.set('sheet', sheet);
            url.searchParams.set('row', row);
            url.searchParams.set('column', column);
            var response = await fetch(url, { credentials: 'same-origin', headers: { Accept: 'application/json' }, signal: controller.signal });
            if (!response.ok) {
                var payload = await response.json().catch(function () { return {}; });
                throw new Error(payload.message || 'The worksheet could not be loaded.');
            }
            var data = await response.json();
            if (token !== generation) return;
            if (!data || !Array.isArray(data.sheets) || !Array.isArray(data.rows)) throw new Error('The worksheet response was invalid.');
            render(data, focusTab);
        } catch (error) {
            if (token !== generation || error.name === 'AbortError') return;
            grid.replaceChildren();
            status.textContent = (error.message || 'The worksheet could not be loaded.') + ' Download the original to view it.';
        } finally {
            if (token === generation) { controller = null; grid.removeAttribute('aria-busy'); }
        }
    }
    document.addEventListener('click', function (event) {
        var trigger = event.target.closest('[data-tosa-workbook-open]');
        if (!trigger) return;
        event.preventDefault();
        opener = trigger;
        endpoint = trigger.dataset.workbookUrl;
        download.href = trigger.dataset.workbookDownload;
        tabs.replaceChildren();
        grid.replaceChildren();
        if (!dialog.open) dialog.showModal();
        load(0, 0, 0, false);
    });
    buttons.forEach(function (button) {
        button.addEventListener('click', function () {
            if (!current) return;
            var row = current.row_page, column = current.column_page;
            if (button.dataset.workbookPage === 'row-prev') row--;
            if (button.dataset.workbookPage === 'row-next') row++;
            if (button.dataset.workbookPage === 'column-prev') column--;
            if (button.dataset.workbookPage === 'column-next') column++;
            load(current.sheet, row, column, false);
        });
    });
    dialog.querySelector('[data-workbook-close]').addEventListener('click', close);
    dialog.addEventListener('cancel', function (event) { event.preventDefault(); close(); });
    dialog.addEventListener('click', function (event) { if (event.target === dialog) close(); });
    dialog.addEventListener('close', function () { if (opener) close(); });
})();
