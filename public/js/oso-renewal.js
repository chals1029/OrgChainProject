(function () {
    'use strict';

    var MAX_FILE_BYTES = 20 * 1024 * 1024;
    var ALLOWED_EXTENSIONS = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'png', 'jpg', 'jpeg'];
    var ACCEPT = ALLOWED_EXTENSIONS.map(function (ext) { return '.' + ext; }).join(',');
    var PAGE_SIZE = 6;
    var IFRAME_TYPES = ['pdf', 'png', 'jpg', 'jpeg'];
    var DOCX_OPTIONS = { inWrapper: true, ignoreWidth: false, ignoreHeight: false };

    function init() {
        var root = document.getElementById('osoRenewalDashboard');
        if (!root || root.dataset.orReady === '1') return;
        root.dataset.orReady = '1';

        initWindowForm();
        initTable(root);
        initFileInputs();
        initAddDialog();
        initEditDialog();
        initPreviewDialog();
        initSubmitGuards(root);
        initClicks(root);
        reopenFailedDialogs();
    }

    function byId(id) {
        return document.getElementById(id);
    }

    function openDialog(dialog) {
        if (!dialog || dialog.open) return;
        if (typeof dialog.showModal === 'function') {
            dialog.showModal();
        } else {
            dialog.setAttribute('open', '');
        }
    }

    function closeDialog(dialog) {
        if (!dialog) return;
        if (typeof dialog.close === 'function') {
            dialog.close();
        } else {
            dialog.removeAttribute('open');
        }
    }

    function cleanText(el) {
        return el ? el.textContent.replace(/\s+/g, ' ').trim() : '';
    }

    function formatBytes(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
    }

    /* Renewal window */

    function initWindowForm() {
        var form = byId('osoRenewalWindowForm');
        var hidden = byId('osoRenewalOpenValue');
        var toggle = byId('osoRenewalToggle');
        var pill = byId('osoRenewalWindowState');
        var opensAt = byId('osoRenewalOpensAt');
        var closesAt = byId('osoRenewalClosesAt');

        if (hidden && toggle) {
            var savedValue = (hidden.dataset.savedValue || hidden.value) === '1' ? '1' : '0';
            var savedPillText = pill ? pill.textContent : '';
            var savedPillClass = pill ? pill.className : '';

            var render = function () {
                var isOpen = hidden.value === '1';
                toggle.setAttribute('aria-pressed', isOpen ? 'true' : 'false');
                toggle.textContent = isOpen ? 'Lock renewal' : 'Enable renewal';
                if (!pill) return;
                if (hidden.value === savedValue) {
                    pill.textContent = savedPillText;
                    pill.className = savedPillClass;
                } else {
                    pill.textContent = isOpen ? 'Will open on save' : 'Will lock on save';
                    pill.className = 'or-pill is-amber';
                }
            };

            toggle.type = 'button';
            toggle.addEventListener('click', function () {
                hidden.value = hidden.value === '1' ? '0' : '1';
                render();
            });
            render();
        }

        if (opensAt && closesAt) {
            var checkDates = function () {
                closesAt.min = opensAt.value || '';
                var invalid = opensAt.value && closesAt.value && closesAt.value < opensAt.value;
                closesAt.setCustomValidity(invalid ? 'The closing date must be on or after the opening date.' : '');
            };
            opensAt.addEventListener('input', checkDates);
            opensAt.addEventListener('change', checkDates);
            closesAt.addEventListener('input', checkDates);
            closesAt.addEventListener('change', checkDates);
            checkDates();
        }
    }

    /* Table: search, filters, pagination, CSV */

    function initTable(root) {
        var rows = Array.prototype.slice.call(root.querySelectorAll('tbody [data-renewal-row]'));
        var search = byId('osoRenewalSearch');
        var statusFilter = byId('osoRenewalStatusFilter');
        var empty = byId('osoRenewalEmpty');
        var count = byId('osoRenewalResultCount');
        var pagination = byId('osoRenewalPagination');
        var exportButton = byId('osoRenewalExport');
        var page = 1;
        var filtered = rows.slice();

        rows.forEach(function (row) {
            if (!row.dataset.search) row.dataset.search = cleanText(row);
            row.dataset.searchNormalized = row.dataset.search.toLowerCase();
        });

        function matchesStatus(row, value) {
            switch (value) {
                case '':
                case 'all':
                    return true;
                case 'active':
                    return row.dataset.officialActive === '1';
                case 'inactive':
                    return row.dataset.officialActive === '0';
                case 'qualified':
                    return row.dataset.qualified === '1';
                case 'not_qualified':
                    return row.dataset.qualified === '0';
                default:
                    return row.dataset.status === value;
            }
        }

        function applyFilters() {
            var terms = search ? search.value.toLowerCase().trim().split(/\s+/).filter(Boolean) : [];
            var status = statusFilter ? statusFilter.value : 'all';
            filtered = rows.filter(function (row) {
                var haystack = row.dataset.searchNormalized;
                for (var i = 0; i < terms.length; i += 1) {
                    if (haystack.indexOf(terms[i]) === -1) return false;
                }
                return matchesStatus(row, status);
            });
            page = 1;
            renderPage();
        }

        function renderPage() {
            var total = filtered.length;
            var pageCount = Math.max(1, Math.ceil(total / PAGE_SIZE));
            if (page > pageCount) page = pageCount;
            var start = (page - 1) * PAGE_SIZE;
            var end = Math.min(start + PAGE_SIZE, total);
            var visible = filtered.slice(start, end);

            rows.forEach(function (row) { row.hidden = true; });
            visible.forEach(function (row) { row.hidden = false; });

            if (empty) empty.hidden = total !== 0;
            if (count) {
                count.textContent = total === 0
                    ? 'No organizations found'
                    : 'Showing ' + (start + 1) + '\u2013' + end + ' of ' + total + ' organization' + (total === 1 ? '' : 's');
            }
            if (exportButton) exportButton.disabled = total === 0;
            renderPagination(pageCount);
        }

        function pageButton(label, targetPage, options) {
            var button = document.createElement('button');
            button.type = 'button';
            button.className = 'or-button is-small';
            button.textContent = label;
            if (options.ariaLabel) button.setAttribute('aria-label', options.ariaLabel);
            if (options.current) {
                button.classList.add('is-primary');
                button.setAttribute('aria-current', 'page');
            }
            if (options.disabled) {
                button.disabled = true;
            } else {
                button.dataset.orPage = String(targetPage);
            }
            return button;
        }

        function pageNumbers(pageCount) {
            if (pageCount <= 7) {
                var all = [];
                for (var i = 1; i <= pageCount; i += 1) all.push(i);
                return all;
            }
            var list = [1];
            var from = Math.max(2, page - 1);
            var to = Math.min(pageCount - 1, page + 1);
            if (page <= 3) to = 4;
            if (page >= pageCount - 2) from = pageCount - 3;
            if (from > 2) list.push(null);
            for (var p = from; p <= to; p += 1) list.push(p);
            if (to < pageCount - 1) list.push(null);
            list.push(pageCount);
            return list;
        }

        function renderPagination(pageCount) {
            if (!pagination) return;
            pagination.replaceChildren();
            pagination.hidden = filtered.length <= PAGE_SIZE;
            if (pagination.hidden) return;
            if (!pagination.getAttribute('aria-label')) pagination.setAttribute('aria-label', 'Organization pages');

            pagination.appendChild(pageButton('\u2039', page - 1, { ariaLabel: 'Previous page', disabled: page === 1 }));
            pageNumbers(pageCount).forEach(function (number) {
                if (number === null) {
                    var gap = document.createElement('span');
                    gap.className = 'or-pagination-gap';
                    gap.setAttribute('aria-hidden', 'true');
                    gap.textContent = '\u2026';
                    pagination.appendChild(gap);
                    return;
                }
                pagination.appendChild(pageButton(String(number), number, {
                    ariaLabel: 'Page ' + number,
                    current: number === page,
                }));
            });
            pagination.appendChild(pageButton('\u203A', page + 1, { ariaLabel: 'Next page', disabled: page === pageCount }));
        }

        if (pagination) {
            pagination.addEventListener('click', function (event) {
                var button = event.target.closest('[data-or-page]');
                if (!button || !pagination.contains(button)) return;
                page = parseInt(button.dataset.orPage, 10) || 1;
                renderPage();
                var focusTarget = pagination.querySelector('[aria-current="page"]');
                if (focusTarget) focusTarget.focus();
            });
        }

        if (search) {
            search.addEventListener('input', applyFilters);
            search.addEventListener('search', applyFilters);
        }
        if (statusFilter) statusFilter.addEventListener('change', applyFilters);
        if (exportButton) {
            exportButton.type = 'button';
            exportButton.addEventListener('click', function () { exportCsv(filtered); });
        }

        applyFilters();
    }

    function csvCell(value) {
        var text = String(value == null ? '' : value);
        if (/^[=+\-@\t\r]/.test(text)) text = "'" + text;
        return '"' + text.replace(/"/g, '""') + '"';
    }

    function exportCsv(rows) {
        if (!rows.length) return;
        var header = ['Organization', 'College', 'Official status', 'Submitted', 'Renewal status', 'Requirements'];
        var selectors = ['.or-name', '.or-college', '.or-official-status', '.or-submitted-date', '.or-renewal-status', '.or-requirements-status'];
        var lines = [header.map(csvCell).join(',')];
        rows.forEach(function (row) {
            lines.push(selectors.map(function (selector) {
                return csvCell(cleanText(row.querySelector(selector)));
            }).join(','));
        });

        var blob = new Blob(['\uFEFF' + lines.join('\r\n') + '\r\n'], { type: 'text/csv;charset=utf-8' });
        var url = URL.createObjectURL(blob);
        var link = document.createElement('a');
        var now = new Date();
        var stamp = now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0') + '-' + String(now.getDate()).padStart(2, '0');
        link.href = url;
        link.download = 'oso-renewal-' + stamp + '.csv';
        link.hidden = true;
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.setTimeout(function () { URL.revokeObjectURL(url); }, 1000);
    }

    /* Official template file pickers */

    function fileStatusTarget(input) {
        var selector = input.dataset.statusTarget;
        if (!selector) return null;
        try {
            return document.querySelector(selector);
        } catch (error) {
            return null;
        }
    }

    function setFileStatus(input, message, state) {
        var target = fileStatusTarget(input);
        if (!target) return;
        target.textContent = message;
        if (state) {
            target.dataset.state = state;
        } else {
            delete target.dataset.state;
        }
        target.classList.toggle('is-error', state === 'error');
        target.hidden = message === '';
    }

    function fileError(input) {
        var files = input.files;
        if (!files || files.length === 0) return input.required ? 'Choose a template file to upload.' : '';
        if (files.length > 1) return 'Choose only one file.';
        var file = files[0];
        var dot = file.name.lastIndexOf('.');
        var ext = dot === -1 ? '' : file.name.slice(dot + 1).toLowerCase();
        if (ALLOWED_EXTENSIONS.indexOf(ext) === -1) {
            return 'Unsupported file type. Allowed: ' + ALLOWED_EXTENSIONS.join(', ').toUpperCase() + '.';
        }
        if (file.size > MAX_FILE_BYTES) return 'File is ' + formatBytes(file.size) + '; the maximum is 20 MB.';
        if (file.size === 0) return 'The selected file is empty.';
        return '';
    }

    function validateFileInput(input, report) {
        var error = fileError(input);
        input.setCustomValidity(error);
        if (error) {
            var hasFile = input.files && input.files.length > 0;
            setFileStatus(input, hasFile || report ? error : '', hasFile || report ? 'error' : '');
            if (report && hasFile && isFocusable(input)) input.reportValidity();
        } else if (input.files && input.files.length === 1) {
            var file = input.files[0];
            setFileStatus(input, file.name + ' (' + formatBytes(file.size) + ')', 'ok');
        } else {
            setFileStatus(input, '', '');
        }
        return error === '';
    }

    function inScope(el) {
        return !!(el && el.closest && el.closest('#osoRenewalDashboard, .or-dialog'));
    }

    function isFocusable(el) {
        if (el.hidden || el.disabled || el.closest('[hidden]')) return false;
        if (el.getClientRects().length === 0) return false;
        return window.getComputedStyle(el).visibility !== 'hidden';
    }

    function initFileInputs() {
        document.querySelectorAll('[data-or-file]').forEach(function (input) {
            if (!inScope(input)) return;
            input.setAttribute('accept', ACCEPT);
            input.multiple = false;
            validateFileInput(input, false);
        });

        document.addEventListener('change', function (event) {
            var input = event.target;
            if (!input.matches || !input.matches('[data-or-file]') || !inScope(input)) return;
            var valid = validateFileInput(input, true);
            var replaceForm = input.closest('[data-or-replace-form]');
            if (replaceForm) handleReplaceChange(replaceForm, input, valid);
        });
    }

    /* Add requirement */

    function initAddDialog() {
        var form = byId('osoRenewalAddForm');
        if (!form) return;
        var code = byId('osoRenewalAddCode');
        var title = byId('osoRenewalAddTitle');
        var file = byId('osoRenewalAddFile');
        var submit = byId('osoRenewalAddSubmit');

        var update = function () {
            if (!submit) return;
            var ready = (!code || code.value.trim() !== '')
                && (!title || title.value.trim() !== '')
                && (!file || (file.files && file.files.length === 1 && fileError(file) === ''));
            submit.disabled = !ready;
        };

        form.addEventListener('input', update);
        form.addEventListener('change', update);
        form.addEventListener('reset', function () {
            window.setTimeout(function () {
                if (file) validateFileInput(file, false);
                update();
            }, 0);
        });
        form.addEventListener('or:sync', update);
        update();
    }

    /* Edit requirement */

    function initEditDialog() {
        var form = byId('osoRenewalEditForm');
        if (!form) return;
        var title = byId('osoRenewalEditTitle');
        var submit = byId('osoRenewalEditSubmit');
        var update = function () {
            if (submit) submit.disabled = !title || title.value.trim() === '';
        };
        form.addEventListener('input', update);
        form.addEventListener('change', update);
        form.addEventListener('or:sync', update);
        update();
    }

    function populateEdit(button) {
        var dialog = byId('osoRenewalEditDialog');
        var form = byId('osoRenewalEditForm');
        if (!dialog || !form) return;
        var data = button.dataset;

        if (data.updateUrl) form.action = data.updateUrl;
        setValue('osoRenewalEditKey', data.key);
        setValue('osoRenewalEditCode', data.code);
        setValue('osoRenewalEditTitle', data.title);
        setValue('osoRenewalEditDescription', data.description);
        var current = byId('osoRenewalEditCurrentFile');
        if (current) {
            current.textContent = data.templateName || 'No template uploaded';
            current.title = current.textContent;
        }

        form.querySelectorAll('[data-or-file]').forEach(function (input) {
            input.value = '';
            validateFileInput(input, false);
        });
        form.dispatchEvent(new Event('or:sync'));
        openDialog(dialog);
    }

    function setValue(id, value) {
        var el = byId(id);
        if (el) el.value = value == null ? '' : value;
    }

    /* Replace requirement template */

    function startReplace(button) {
        var requirement = button.closest('.or-requirement');
        var form = requirement ? requirement.querySelector('[data-or-replace-form]') : null;
        var input = form ? form.querySelector('[data-or-file]') : null;
        if (!input || form.dataset.orSubmitting === '1') return;
        input.value = '';
        input.setCustomValidity('');
        setFileStatus(input, '', '');
        input.click();
    }

    function handleReplaceChange(form, input, valid) {
        if (!valid || form.dataset.orSubmitting === '1') return;
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }
        form.dataset.orSubmitting = '1';
        setFileStatus(input, 'Uploading ' + input.files[0].name + '\u2026', 'busy');
        var requirement = form.closest('.or-requirement');
        (requirement || form).querySelectorAll('[data-or-replace]').forEach(function (button) {
            if (button.disabled) return;
            button.disabled = true;
            button.dataset.orLocked = '1';
        });
        if (typeof form.requestSubmit === 'function') {
            form.requestSubmit();
        } else {
            form.submit();
        }
    }

    /* Delete requirement */

    function populateDelete(button) {
        var dialog = byId('osoRenewalDeleteDialog');
        var form = byId('osoRenewalDeleteForm');
        if (!dialog || !form) return;
        if (button.dataset.deleteUrl) form.action = button.dataset.deleteUrl;
        setValue('osoRenewalDeleteKey', button.dataset.key);
        var title = byId('osoRenewalDeleteTitle');
        if (title) title.textContent = button.dataset.title || '';
        openDialog(dialog);
    }

    /* Eligibility */

    function openEligibility(button) {
        if (typeof window.openManageQualificationModal !== 'function') return;
        var data;
        try {
            data = JSON.parse(button.dataset.organization || 'null');
        } catch (error) {
            return;
        }
        if (data) window.openManageQualificationModal(data);
    }

    /* Template preview */

    var preview = { token: 0, controller: null };

    function previewEls() {
        return {
            dialog: byId('osoRenewalPreviewDialog'),
            title: byId('osoRenewalPreviewTitle'),
            download: byId('osoRenewalPreviewDownload'),
            external: byId('osoRenewalPreviewExternal'),
            frame: byId('osoRenewalPreviewFrame'),
            docx: byId('osoRenewalPreviewDocx'),
            message: byId('osoRenewalPreviewMessage'),
        };
    }

    function resetPreview() {
        preview.token += 1;
        if (preview.controller) {
            preview.controller.abort();
            preview.controller = null;
        }
        var els = previewEls();
        if (els.frame) {
            els.frame.removeAttribute('src');
            els.frame.hidden = true;
        }
        if (els.docx) {
            els.docx.replaceChildren();
            els.docx.hidden = true;
        }
        setPreviewMessage('', false);
    }

    function setPreviewMessage(text, isError) {
        var message = byId('osoRenewalPreviewMessage');
        if (!message) return;
        message.textContent = text;
        message.hidden = text === '';
        message.classList.toggle('is-error', !!isError);
        if (isError) {
            message.setAttribute('role', 'alert');
        } else {
            message.removeAttribute('role');
        }
    }

    function initPreviewDialog() {
        var dialog = byId('osoRenewalPreviewDialog');
        if (!dialog) return;
        dialog.addEventListener('close', resetPreview);
    }

    function setLink(link, href, filename) {
        if (!link) return;
        if (href) {
            link.href = href;
            link.hidden = false;
            link.removeAttribute('aria-disabled');
        } else {
            link.removeAttribute('href');
            link.hidden = true;
        }
        if (filename !== undefined) {
            if (filename) {
                link.setAttribute('download', filename);
            } else {
                link.setAttribute('download', '');
            }
        }
    }

    function openPreview(button) {
        var els = previewEls();
        if (!els.dialog) return;
        resetPreview();

        var data = button.dataset;
        var url = data.url || '';
        var type = (data.type || '').toLowerCase().replace(/^\./, '');
        var token = preview.token;

        if (els.title) els.title.textContent = data.title || data.filename || 'Template preview';
        setLink(els.download, data.downloadUrl || '', data.filename || '');
        setLink(els.external, url);
        openDialog(els.dialog);

        if (!url) {
            setPreviewMessage('No template file is available for this requirement.', true);
            return;
        }

        if (IFRAME_TYPES.indexOf(type) !== -1) {
            if (!els.frame) {
                setPreviewMessage('Preview is unavailable here. Use Download or Open to view the file.', true);
                return;
            }
            els.frame.title = (data.title || 'Template') + ' preview';
            els.frame.hidden = false;
            els.frame.src = url;
            return;
        }

        if (type === 'docx') {
            renderDocx(url, token, els);
            return;
        }

        setPreviewMessage(
            (type ? type.toUpperCase() + ' files' : 'This file type') +
            ' cannot be previewed in the browser. Download the file or open it in your document app.',
            false
        );
    }

    function renderDocx(url, token, els) {
        if (!els.docx) {
            setPreviewMessage('Preview is unavailable here. Use Download to view the file.', true);
            return;
        }
        if (!window.docx || typeof window.docx.renderAsync !== 'function') {
            setPreviewMessage('The Word preview library failed to load. Download the file to view it.', true);
            return;
        }

        var controller = typeof AbortController === 'function' ? new AbortController() : null;
        preview.controller = controller;
        setPreviewMessage('Loading preview\u2026', false);

        fetch(url, {
            credentials: 'same-origin',
            headers: { Accept: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' },
            signal: controller ? controller.signal : undefined,
        }).then(function (response) {
            if (!response.ok) throw new Error('The server returned ' + response.status + ' while loading the template.');
            return response.arrayBuffer();
        }).then(function (buffer) {
            if (token !== preview.token) return null;
            var container = document.createElement('div');
            container.className = 'or-preview-docx-render';
            els.docx.replaceChildren(container);
            return window.docx.renderAsync(buffer, container, null, DOCX_OPTIONS).then(function () {
                if (token !== preview.token) {
                    container.remove();
                    return;
                }
                if (preview.controller === controller) preview.controller = null;
                els.docx.hidden = false;
                setPreviewMessage('', false);
            });
        }).catch(function (error) {
            if (token !== preview.token || (error && error.name === 'AbortError')) return;
            if (preview.controller === controller) preview.controller = null;
            els.docx.replaceChildren();
            els.docx.hidden = true;
            var detail = error && error.message ? ' ' + error.message : '';
            setPreviewMessage('Could not preview this document.' + detail + ' Download the file to view it.', true);
        });
    }

    /* Native submit guards */

    function initSubmitGuards(root) {
        var forms = [
            byId('osoRenewalWindowForm'),
            byId('osoRenewalAddForm'),
            byId('osoRenewalEditForm'),
            byId('osoRenewalDeleteForm'),
        ].filter(Boolean);

        forms.forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (form.dataset.orSubmitting === '1') {
                    event.preventDefault();
                    return;
                }
                form.dataset.orSubmitting = '1';
            });
        });

        window.addEventListener('pageshow', function (event) {
            if (!event.persisted) return;
            forms.forEach(function (form) { delete form.dataset.orSubmitting; });
            root.querySelectorAll('[data-or-replace-form][data-or-submitting="1"]').forEach(function (form) {
                delete form.dataset.orSubmitting;
                var requirement = form.closest('.or-requirement');
                (requirement || form).querySelectorAll('[data-or-replace][data-or-locked="1"]').forEach(function (button) {
                    button.disabled = false;
                    delete button.dataset.orLocked;
                });
                var input = form.querySelector('[data-or-file]');
                if (input) {
                    input.value = '';
                    validateFileInput(input, false);
                }
            });
        });
    }

    /* Delegated clicks */

    function initClicks(root) {
        document.addEventListener('click', function (event) {
            var target = event.target;
            if (!target.closest) return;
            var close = target.closest('[data-or-close]');
            if (!close || !inScope(close)) return;
            event.preventDefault();
            closeDialog(close.closest('dialog'));
        });

        root.addEventListener('click', function (event) {
            var target = event.target;
            if (!target.closest || target.closest('[data-or-close]')) return;

            var action = target.closest('[data-or-add],[data-or-edit],[data-or-replace],[data-or-delete],[data-or-eligibility],[data-or-view]');
            if (!action || !root.contains(action) || action.disabled) return;
            event.preventDefault();

            if (action.hasAttribute('data-or-add')) {
                var addForm = byId('osoRenewalAddForm');
                if (addForm) addForm.dispatchEvent(new Event('or:sync'));
                openDialog(byId('osoRenewalAddDialog'));
            } else if (action.hasAttribute('data-or-edit')) {
                populateEdit(action);
            } else if (action.hasAttribute('data-or-replace')) {
                startReplace(action);
            } else if (action.hasAttribute('data-or-delete')) {
                populateDelete(action);
            } else if (action.hasAttribute('data-or-eligibility')) {
                openEligibility(action);
            } else if (action.hasAttribute('data-or-view')) {
                openPreview(action);
            }
        });
    }

    function reopenFailedDialogs() {
        document.querySelectorAll('dialog[data-reopen="true"]').forEach(function (dialog) {
            if (!inScope(dialog)) return;
            dialog.removeAttribute('data-reopen');
            var form = dialog.querySelector('form');
            if (form) form.dispatchEvent(new Event('or:sync'));
            openDialog(dialog);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
