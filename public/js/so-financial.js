/* SO Financial Report dashboard: chart, history filters, cash, create and preview dialogs. */
(() => {
    'use strict';

    const dataEl = document.getElementById('soFinancialData');
    if (!dataEl) return;

    const data = JSON.parse(dataEl.textContent || '{}');
    const reports = data.reports || {};
    const peso = new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' });
    const isAmount = (value) => value !== null && value !== undefined && value !== '' && Number.isFinite(Number(value));
    const money = (value) => (isAmount(value) ? peso.format(Number(value)) : 'Not recorded');
    const byId = (id) => document.getElementById(id);
    const el = (tag, className, text) => {
        const node = document.createElement(tag);
        if (className) node.className = className;
        if (text !== undefined) node.textContent = text;
        return node;
    };

    /* Dashboard GET filters */
    const filterForm = byId('finFilterForm');
    if (filterForm) {
        filterForm.querySelector('[data-fin-filter-apply]')?.setAttribute('hidden', '');
        filterForm.querySelectorAll('[data-fin-autosubmit]').forEach((select) => {
            select.addEventListener('change', () => filterForm.requestSubmit());
        });
    }

    /* Event cash-flow chart from recorded activities */
    const chartCanvas = byId('finEventChart');
    if (chartCanvas) {
        const activities = Array.isArray(data.activities) ? data.activities : [];
        if (typeof window.Chart === 'function') {
            const labels = activities.map((activity) => String(activity.name));
            new window.Chart(chartCanvas, {
                type: 'bar',
                data: {
                    labels,
                    datasets: [
                        { label: 'Incoming', data: activities.map((a) => Number(a.inflow) || 0), backgroundColor: '#059669', borderRadius: 6, maxBarThickness: 30 },
                        { label: 'Outgoing', data: activities.map((a) => Number(a.outflow) || 0), backgroundColor: '#8b1828', borderRadius: 6, maxBarThickness: 30 },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: { callbacks: { label: (ctx) => `${ctx.dataset.label}: ${peso.format(ctx.parsed.y)}` } },
                    },
                    scales: {
                        y: { beginAtZero: true, ticks: { callback: (value) => peso.format(value) }, grid: { color: '#f3eaec' } },
                        x: {
                            grid: { display: false },
                            ticks: {
                                maxRotation: 40,
                                callback(value) {
                                    const label = this.getLabelForValue(value);
                                    return label.length > 18 ? `${label.slice(0, 17)}…` : label;
                                },
                            },
                        },
                    },
                },
            });
        } else {
            byId('finChartWrap').hidden = true;
            byId('finChartFallbackNote').hidden = false;
            byId('finChartTable')?.classList.remove('fin-sr-only');
        }
    }

    /* Report history: client-side search / year / semester */
    const historySearch = byId('finHistorySearch');
    if (historySearch) {
        const historyYear = byId('finHistoryYear');
        const historySemester = byId('finHistorySemester');
        const rows = Array.from(document.querySelectorAll('[data-fin-history-row]'));
        const tableWrap = byId('finHistoryRows')?.closest('.fin-table-wrap');
        const noMatch = byId('finHistoryNoMatch');
        const count = byId('finHistoryCount');

        const filterHistory = () => {
            const query = historySearch.value.trim().toLowerCase();
            const year = historyYear.value;
            const semester = historySemester.value;
            let shown = 0;
            rows.forEach((row) => {
                const match = (!query || row.dataset.search.includes(query))
                    && (!year || row.dataset.year === year)
                    && (!semester || row.dataset.semester === semester);
                row.hidden = !match;
                if (match) shown += 1;
            });
            if (tableWrap) tableWrap.hidden = shown === 0;
            noMatch.hidden = shown !== 0;
            count.textContent = `${shown} of ${rows.length} history reports shown.`;
        };

        historySearch.addEventListener('input', filterHistory);
        historyYear.addEventListener('change', filterHistory);
        historySemester.addEventListener('change', filterHistory);
    }

    /* Dialog helpers */
    const openDialog = (dialog) => {
        if (dialog && !dialog.open) dialog.showModal();
    };
    document.querySelectorAll('.fin-dialog').forEach((dialog) => {
        dialog.querySelectorAll('[data-fin-close]').forEach((button) => {
            button.addEventListener('click', () => dialog.close());
        });
    });

    /* Opening balance and cash inflow dialogs (native forms) */
    document.querySelectorAll('[data-fin-open]').forEach((button) => {
        button.addEventListener('click', () => {
            const target = byId(button.dataset.finOpen);
            button.closest('dialog')?.close();
            openDialog(target);
            target?.querySelector('input:not([type="hidden"]), select')?.focus();
        });
    });
    ['finOpeningDialog', 'finIncomeDialog'].forEach((id) => {
        const dialog = byId(id);
        if (dialog?.hasAttribute('data-open-on-load')) openDialog(dialog);
    });
    document.querySelectorAll('[data-fin-cash-form]').forEach((form) => {
        form.addEventListener('submit', () => {
            form.querySelectorAll('button[type="submit"]').forEach((button) => { button.disabled = true; });
        });
    });

    /* Create Report dialog */
    const createDialog = byId('finCreateDialog');
    const createForm = byId('finCreateForm');
    const workbookInput = byId('finWorkbookInput');
    const workbookError = byId('finWorkbookError');
    const showWorkbookError = (message) => {
        workbookError.textContent = message;
        workbookError.hidden = !message;
    };

    document.querySelectorAll('[data-fin-open-create]').forEach((button) => {
        button.addEventListener('click', () => {
            openDialog(createDialog);
            createForm.querySelector('input[name="name"]')?.focus();
        });
    });
    if (createDialog?.hasAttribute('data-open-on-load')) openDialog(createDialog);

    workbookInput?.addEventListener('change', () => showWorkbookError(''));
    createForm?.addEventListener('submit', (event) => {
        const file = workbookInput.files?.[0];
        let message = '';
        if (!file) message = 'Choose the completed Financial Report workbook.';
        else if (!/\.xlsx$/i.test(file.name)) message = `${file.name} is not an .xlsx workbook. Save the report as Excel Workbook (.xlsx) and choose it again.`;
        if (message) {
            event.preventDefault();
            event.stopPropagation();
            showWorkbookError(message);
            workbookInput.focus();
        }
    });

    /* Submit gate: a report must be opened with View in this page first */
    const viewed = new Set();
    const markViewed = (id) => {
        const key = String(id);
        viewed.add(key);
        const button = document.querySelector(`[data-fin-submit="${CSS.escape(key)}"]`);
        if (!button) return;
        button.disabled = false;
        const help = document.querySelector(`[data-fin-submit-help="${CSS.escape(key)}"]`);
        if (help) {
            help.textContent = 'Reviewed — ready to submit this Financial Report to OSO.';
            help.classList.add('is-ready');
        }
    };

    document.querySelectorAll('[data-fin-submit-form]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            const key = form.dataset.finSubmitForm;
            const report = reports[key];
            if (!viewed.has(key)) {
                event.preventDefault();
                return;
            }
            const label = report ? `${report.title} (${report.academic_year} · ${report.semester})` : 'this Financial Report';
            if (!window.confirm(`Submit ${label} to OSO? This Financial Report is locked while OSO reviews it.`)) {
                event.preventDefault();
                return;
            }
            form.querySelectorAll('button[type="submit"]').forEach((button) => { button.disabled = true; });
        });
    });

    /* Preview dialog */
    const previewDialog = byId('finPreviewDialog');
    if (!previewDialog) return;

    const previewTitle = byId('finPreviewTitle');
    const previewMeta = byId('finPreviewMeta');
    const previewDownload = byId('finPreviewDownload');
    const previewStatus = byId('finPreviewStatus');
    const previewWorkbook = byId('finPreviewWorkbook');
    const previewTabs = byId('finPreviewTabs');
    const previewPanel = byId('finPreviewPanel');
    const previewLimit = byId('finPreviewLimit');
    const previewFile = byId('finPreviewFile');
    const previewInflow = byId('finPreviewInflow');
    const previewOutflow = byId('finPreviewOutflow');
    const previewBalance = byId('finPreviewBalance');
    const imageExtensions = ['png', 'jpg', 'jpeg', 'gif', 'webp'];

    let previewToken = 0;
    let previewController = null;
    let previewObjectUrl = null;
    const setStatus = (message, isError = false) => {
        previewStatus.textContent = message;
        previewStatus.classList.toggle('is-error', isError);
    };

    const resetPreview = () => {
        previewToken += 1;
        previewController?.abort();
        previewController = null;
        previewFile.replaceChildren();
        previewFile.hidden = true;
        if (previewObjectUrl) {
            URL.revokeObjectURL(previewObjectUrl);
            previewObjectUrl = null;
        }
        previewWorkbook.hidden = true;
        previewTabs.replaceChildren();
        previewPanel.replaceChildren();
        previewLimit.textContent = '';
        setStatus('');
    };

    const responseError = async (response) => {
        try {
            const body = await response.clone().json();
            if (body && typeof body.message === 'string' && body.message) return body.message;
        } catch (error) { /* non-JSON error body */ }
        return `The preview could not be loaded (HTTP ${response.status}).`;
    };

    const columnName = (index) => {
        let name = '';
        for (let n = index + 1; n > 0; n = Math.floor((n - 1) / 26)) {
            name = String.fromCharCode(65 + ((n - 1) % 26)) + name;
        }
        return name;
    };

    const buildSheetTable = (rows, columnCount) => {
        const table = el('table', 'fin-sheet-table');
        const headRow = el('tr');
        headRow.append(el('th', '', ''));
        for (let c = 0; c < columnCount; c += 1) {
            const th = el('th', '', columnName(c));
            th.scope = 'col';
            headRow.append(th);
        }
        const thead = el('thead');
        thead.append(headRow);
        table.append(thead);

        const tbody = el('tbody');
        rows.forEach((row, rowIndex) => {
            const tr = el('tr');
            const rowHead = el('th', '', String(rowIndex + 1));
            rowHead.scope = 'row';
            tr.append(rowHead);
            (Array.isArray(row) ? row : []).forEach((cell, colIndex) => {
                if (cell && cell.skip) return;
                const td = el('td', '', cell && cell.text !== undefined && cell.text !== null ? String(cell.text) : '');
                const colspan = Math.min(Number(cell?.colspan) || 1, columnCount - colIndex);
                const rowspan = Math.min(Number(cell?.rowspan) || 1, rows.length - rowIndex);
                if (colspan > 1) td.colSpan = colspan;
                if (rowspan > 1) td.rowSpan = rowspan;
                tr.append(td);
            });
            tbody.append(tr);
        });
        table.append(tbody);
        return table;
    };

    const selectSheet = (sheets, index, focusTab = false) => {
        const sheet = sheets[index];
        const tabs = Array.from(previewTabs.children);
        tabs.forEach((tab, i) => {
            const active = i === index;
            tab.setAttribute('aria-selected', active ? 'true' : 'false');
            tab.tabIndex = active ? 0 : -1;
        });
        if (focusTab) tabs[index]?.focus();
        previewPanel.setAttribute('aria-labelledby', tabs[index]?.id || '');

        const rows = Array.isArray(sheet.rows) ? sheet.rows : [];
        if (!rows.length) {
            previewPanel.replaceChildren(el('p', 'fin-sheet-empty', 'This worksheet has no cell values.'));
            previewLimit.textContent = '';
        } else {
            const columnCount = Math.max(...rows.map((row) => (Array.isArray(row) ? row.length : 0)));
            previewPanel.replaceChildren(buildSheetTable(rows, columnCount));
            const totalRows = Math.max(Number(sheet.total_rows) || 0, rows.length);
            const totalColumns = Math.max(Number(sheet.total_columns) || 0, columnCount);
            previewLimit.textContent = totalRows > rows.length || totalColumns > columnCount
                ? `Preview limited to the first ${rows.length} of ${totalRows} rows and ${columnCount} of ${totalColumns} columns. Download the original for the complete worksheet.`
                : `Showing all ${totalRows} rows and ${totalColumns} columns.`;
        }
        previewPanel.scrollTop = 0;
        previewPanel.scrollLeft = 0;
    };

    const renderWorkbook = (payload) => {
        const sheets = payload.sheets;
        previewInflow.textContent = money(payload.cash_inflow);
        previewOutflow.textContent = money(payload.cash_outflow);
        previewBalance.textContent = money(payload.balance);

        sheets.forEach((sheet, index) => {
            const tab = el('button', 'fin-sheet-tab', String(sheet.name || `Sheet ${index + 1}`));
            tab.type = 'button';
            tab.id = `finSheetTab${index}`;
            tab.setAttribute('role', 'tab');
            tab.setAttribute('aria-controls', 'finPreviewPanel');
            tab.addEventListener('click', () => selectSheet(sheets, index));
            tab.addEventListener('keydown', (event) => {
                const keys = { ArrowRight: index + 1, ArrowLeft: index - 1, Home: 0, End: sheets.length - 1 };
                if (!(event.key in keys)) return;
                event.preventDefault();
                selectSheet(sheets, (keys[event.key] + sheets.length) % sheets.length, true);
            });
            previewTabs.append(tab);
        });

        previewWorkbook.hidden = false;
        if (sheets.length) selectSheet(sheets, 0);
        else previewPanel.replaceChildren(el('p', 'fin-sheet-empty', 'This workbook has no worksheets.'));
    };

    const loadWorkbook = async (report, token) => {
        setStatus('Loading workbook preview…');
        const controller = new AbortController();
        previewController = controller;
        try {
            const response = await fetch(report.preview_url, {
                credentials: 'same-origin',
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                signal: controller.signal,
            });
            if (!response.ok) throw new Error(await responseError(response));
            const payload = await response.json();
            if (token !== previewToken) return;
            if (!payload || !Array.isArray(payload.sheets)) throw new Error('The preview response was not in the expected format.');
            if (payload.title) previewTitle.textContent = payload.title;
            if (payload.download_url) previewDownload.href = payload.download_url;
            renderWorkbook(payload);
            setStatus('');
            markViewed(report.id);
        } catch (error) {
            if (error.name === 'AbortError' || token !== previewToken) return;
            setStatus(`${error.message || 'The preview could not be loaded.'} You can still download the original file.`, true);
        }
    };

    const showDownloadOnly = (report) => {
        const note = el('div', 'fin-preview-note');
        const icon = el('i', 'bi bi-file-earmark-lock');
        icon.setAttribute('aria-hidden', 'true');
        const link = el('a', 'org-btn org-btn-primary org-btn-sm');
        link.href = report.download_url;
        link.append(el('i', 'bi bi-download'), document.createTextNode(' Download original'));
        note.append(
            icon,
            el('strong', '', 'Preview not available for this file type'),
            el('span', '', data.reviewer_mode
                ? `${report.original_name} cannot be previewed in this browser. Download the original and open it in an application that supports its format.`
                : `${report.original_name} cannot be previewed in this browser. Download the original to review it. To submit a new Financial Report, upload a completed .xlsx workbook with Create Report.`),
            link,
        );
        previewFile.replaceChildren(note);
        previewFile.hidden = false;
    };

    const loadFile = async (report, token, kind) => {
        setStatus('Loading file…');
        const controller = new AbortController();
        previewController = controller;
        try {
            const url = new URL(report.download_url, window.location.href);
            url.searchParams.delete('download');
            const response = await fetch(url.toString(), { credentials: 'same-origin', signal: controller.signal });
            if (!response.ok) throw new Error(`The file could not be loaded (HTTP ${response.status}).`);
            let blob = await response.blob();
            if (token !== previewToken) return;
            if (kind === 'pdf') {
                const signature = await blob.slice(0, 5).text();
                if (token !== previewToken) return;
                if (signature !== '%PDF-') throw new Error('The stored file is not a valid PDF, so it cannot be previewed.');
                blob = new Blob([blob], { type: 'application/pdf' });
            } else if (!blob.type.startsWith('image/') || blob.type === 'image/svg+xml') {
                throw new Error('The stored file is not a supported image, so it cannot be previewed.');
            }

            previewObjectUrl = URL.createObjectURL(blob);
            if (kind === 'pdf') {
                const frame = el('iframe');
                frame.title = report.original_name;
                frame.src = previewObjectUrl;
                previewFile.replaceChildren(frame);
                previewFile.hidden = false;
                setStatus('');
                markViewed(report.id);
                return;
            }

            const image = el('img');
            image.alt = report.original_name;
            image.addEventListener('load', () => {
                if (token !== previewToken) return;
                setStatus('');
                markViewed(report.id);
            });
            image.addEventListener('error', () => {
                if (token !== previewToken) return;
                previewFile.hidden = true;
                setStatus('The image could not be displayed. You can still download the original file.', true);
            });
            image.src = previewObjectUrl;
            previewFile.replaceChildren(image);
            previewFile.hidden = false;
        } catch (error) {
            if (error.name === 'AbortError' || token !== previewToken) return;
            setStatus(`${error.message || 'The file could not be loaded.'} You can still download the original file.`, true);
        }
    };

    const openPreview = (id) => {
        const report = reports[String(id)];
        if (!report || !report.has_file) return;
        resetPreview();
        const token = previewToken;
        previewTitle.textContent = report.title;
        previewMeta.textContent = [report.academic_year, report.semester, report.original_name].filter(Boolean).join(' · ');
        previewDownload.href = report.download_url;
        openDialog(previewDialog);

        if (report.is_native_ar) {
            setStatus('Loading submitted Accomplishment Report…');
            const frame = el('iframe');
            frame.title = report.title;
            frame.addEventListener('load', () => {
                if (token === previewToken) setStatus('');
            });
            frame.src = report.preview_url;
            previewFile.replaceChildren(frame);
            previewFile.hidden = false;
            return;
        }

        if (report.is_workbook) {
            loadWorkbook(report, token);
            return;
        }
        const extension = String(report.original_name || '').split('.').pop().toLowerCase();
        if (extension === 'pdf') loadFile(report, token, 'pdf');
        else if (imageExtensions.includes(extension)) loadFile(report, token, 'image');
        else showDownloadOnly(report);
    };

    document.addEventListener('click', (event) => {
        const button = event.target.closest('[data-fin-view]');
        if (button) openPreview(button.dataset.finView);
    });
    previewDialog.addEventListener('click', (event) => {
        if (event.target === previewDialog) previewDialog.close();
    });
    previewDialog.addEventListener('close', resetPreview);
})();
