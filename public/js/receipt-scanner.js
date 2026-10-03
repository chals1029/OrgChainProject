(() => {
    'use strict';
    const form = document.getElementById('soExpenseForm');
    if (!form) return;
    const byId = id => document.getElementById(id);
    const review = byId('soReviewCheck');
    const checkbox = review?.querySelector('input');
    const submit = form.querySelector('[type="submit"]');
    const items = byId('soExpenseItems');
    const rowTemplate = byId('soExpenseRowTemplate');
    const states = new WeakMap();
    let lastFailedRow = null;
    const requiredReceiptFields = ['supplier', 'unit_cost', 'expense_date', 'receipt_reference'];
    const incompleteReceiptMessage = 'Receipt is blurry or incomplete. Upload a clear, uncropped photo showing the merchant, total amount, transaction date, and receipt or reference number.';

    const setGlobalState = (kind, title, message) => {
        const status = byId('soOcrStatus');
        if (!status) return;
        status.dataset.state = kind;
        byId('soOcrTitle').textContent = title;
        byId('soOcrText').textContent = message;
    };

    const rows = () => [...document.querySelectorAll('[data-expense-row]')];
    const fields = row => Object.fromEntries([...row.querySelectorAll('[data-field]')].map(field => [field.dataset.field, field]));
    const fileInput = row => row.dataset.rowIndex === '0' ? byId('soReceiptInput') : row.querySelector('[data-receipt-input]');
    const setRowMessage = (row, kind, title, message) => {
        const status = row.querySelector('[data-row-status]');
        if (status) {
            status.dataset.state = kind;
            status.querySelector('[data-row-title]').textContent = title;
            status.querySelector('[data-row-text]').textContent = message;
        }
        if (row.dataset.rowIndex === '0') setGlobalState(kind, title, message);
    };

    const rowPreview = row => {
        if (row.dataset.rowIndex === '0') return { box: byId('soReceiptPreview'), image: byId('soReceiptPreviewImg'), name: byId('soReceiptPreviewName') };
        return { box: row.querySelector('[data-preview]'), image: row.querySelector('[data-preview-img]'), name: row.querySelector('[data-preview-name]') };
    };

    const showPreview = (row, file) => {
        const preview = rowPreview(row);
        if (!preview.box) return;
        if (preview.box.dataset.objectUrl) URL.revokeObjectURL(preview.box.dataset.objectUrl);
        preview.box.dataset.objectUrl = '';
        if (preview.image) { preview.image.removeAttribute('src'); preview.image.hidden = true; }
        preview.box.hidden = !file;
        if (!file) return;
        preview.name.textContent = `${file.name} (${Math.round(file.size / 1024)} KB)`;
        if (file.type.startsWith('image/') && preview.image) {
            const url = URL.createObjectURL(file);
            preview.box.dataset.objectUrl = url;
            preview.image.src = url;
            preview.image.hidden = false;
        }
    };

    const addHints = row => {
        const map = fields(row);
        ['supplier', 'unit_cost', 'expense_date', 'receipt_reference', 'receipt_type', 'payment_method'].forEach(key => {
            const field = map[key];
            if (!field || field.dataset.hinted) return;
            const hint = document.createElement('small');
            hint.className = 'so-field-confidence';
            hint.dataset.confidenceFor = key;
            field.setAttribute('aria-describedby', `${field.name}-confidence`);
            field.insertAdjacentElement('afterend', hint);
            field.dataset.hinted = '1';
        });
    };

    const hint = (row, key) => row.querySelector(`[data-confidence-for="${key}"]`);

    const updateSubmit = () => {
        const rowStates = rows().map(row => states.get(row));
        const ready = rowStates.length > 0 && rowStates.every(state => state?.ready && !state.pending);
        submit.disabled = !ready || !checkbox?.checked || !byId('receiptActivityId')?.value;
        review.hidden = !ready;
    };

    const clearScannedFields = row => {
        const map = fields(row);
        ['supplier', 'unit_cost', 'expense_date', 'receipt_reference'].forEach(key => { if (map[key]) map[key].value = ''; });
        ['receipt_type', 'payment_method'].forEach(key => { if (map[key]) map[key].value = 'unknown'; });
        const scanId = row.querySelector('[data-scan-id]');
        if (scanId) scanId.value = '';
        ['supplier', 'unit_cost', 'expense_date', 'receipt_reference', 'receipt_type', 'payment_method'].forEach(key => {
            const fieldHint = hint(row, key);
            if (fieldHint) { fieldHint.textContent = ''; fieldHint.dataset.review = ''; }
        });
    };

    const resetRow = (row, preserveItem = true) => {
        const previous = states.get(row);
        previous?.pending?.abort();
        states.set(row, { version: (previous?.version || 0) + 1, pending: null, ready: false });
        clearScannedFields(row);
        if (!preserveItem) {
            const map = fields(row);
            ['item_name', 'category'].forEach(key => { if (map[key]) map[key].value = ''; });
            if (map.quantity) map.quantity.value = '1';
        }
        const file = fileInput(row);
        if (file) file.value = '';
        const uploadStatus = row.dataset.rowIndex === '0' ? byId('soReceiptUploadStatus') : row.querySelector('[data-upload-status]');
        if (uploadStatus) uploadStatus.textContent = 'No receipt selected.';
        showPreview(row, null);
        setRowMessage(row, '', 'Waiting for receipt', 'Choose the matching receipt file for this item.');
        updateSubmit();
    };

    const currentFile = row => fileInput(row)?.files?.[0];
    const errorMessage = (response, result) => [401, 419].includes(response.status)
        ? 'Your session expired. Reload and sign in, then choose the file again.'
        : response.status === 429
            ? 'Too many validation attempts. Wait a minute and retry.'
            : Object.values(result.errors || {}).flat()[0] || result.message || 'The validation service is unavailable. Retry shortly; no expense was recorded.';

    const setDetectedFields = (row, result) => {
        const map = fields(row);
        Object.entries(map).forEach(([key, field]) => {
            const detected = result.fields?.[key];
            if (!detected) return;
            const value = detected.value;
            field.value = value ?? (field.tagName === 'SELECT' ? 'unknown' : '');
            const fieldHint = hint(row, key);
            if (fieldHint) {
                const missing = value == null || value === 'unknown';
                fieldHint.textContent = missing ? 'Not identified — enter or check this field.' : `${detected.confidence}% extraction confidence — ${detected.confidence >= 80 ? 'check against receipt' : 'needs close review'}`;
                fieldHint.dataset.review = String(missing || detected.confidence < 80);
            }
        });
        ['supplier', 'unit_cost', 'expense_date', 'receipt_reference', 'receipt_type', 'payment_method'].forEach(key => {
            if (result.fields?.[key]) return;
            const fieldHint = hint(row, key);
            if (fieldHint) {
                fieldHint.textContent = 'Not identified — enter or check this field.';
                fieldHint.dataset.review = 'true';
            }
        });
    };

    async function scanRow(row) {
        const file = currentFile(row);
        const previous = states.get(row) || { version: 0 };
        previous.version += 1;
        previous.pending?.abort();
        const version = previous.version;
        const state = { ...previous, version, pending: null, ready: false };
        states.set(row, state);
        clearScannedFields(row);
        showPreview(row, file);
        if (row.dataset.rowIndex === '0') byId('soRetryScan').hidden = true;
        row.querySelector('[data-row-retry]')?.setAttribute('hidden', 'hidden');
        const uploadStatus = row.dataset.rowIndex === '0' ? byId('soReceiptUploadStatus') : row.querySelector('[data-upload-status]');
        if (uploadStatus) uploadStatus.textContent = file ? `${file.name} selected — validating…` : 'No receipt selected.';
        checkbox.checked = false;
        lastFailedRow = null;
        if (!file) {
            setRowMessage(row, '', 'Waiting for receipt', 'Choose the matching receipt file for this item.');
            updateSubmit();
            return;
        }
        if (file.size > 10 * 1024 * 1024) {
            setRowMessage(row, 'needs-review', 'File is too large', 'Choose a receipt file of 10 MB or less.');
            updateSubmit();
            return;
        }
        const extension = file.name.split('.').pop().toLowerCase();
        if (extension === 'docx') {
            setRowMessage(row, 'scanning', 'Checking DOCX receipt…', 'Reading the document structure and receipt labels before it can be saved.');
            const controller = new AbortController(); state.pending = controller;
            try {
                const body = new FormData(); body.append('receipt', file);
                const response = await fetch(form.dataset.validateDocumentUrl, { method: 'POST', credentials: 'same-origin', signal: controller.signal, headers: { Accept: 'application/json', 'X-CSRF-TOKEN': form.elements._token.value }, body });
                const result = await response.json().catch(() => ({}));
                if (states.get(row)?.version !== version) return;
                if (!response.ok || !result.valid) throw new Error(errorMessage(response, result));
                const map = fields(row);
                if (map.receipt_type) map.receipt_type.value = 'paper_receipt';
                state.ready = true; state.pending = null; states.set(row, state);
                ['receipt_type', 'payment_method'].forEach(key => { const fieldHint = hint(row, key); if (fieldHint) fieldHint.textContent = 'DOCX structure checked — manually review this field.'; });
                setRowMessage(row, 'validated', 'DOCX appears to be a receipt', `${result.message} The contents are not proof of authenticity.`);
            } catch (error) {
                if (states.get(row)?.version !== version) return;
                state.pending = null; states.set(row, state); lastFailedRow = row;
                setRowMessage(row, 'needs-review', 'DOCX is not accepted as a receipt', error.name === 'AbortError' ? 'Validation timed out. Choose a smaller file or retry.' : error.message);
                const retryButton = row.querySelector('[data-row-retry]') || byId('soRetryScan');
                if (retryButton) retryButton.hidden = false;
            }
            updateSubmit();
            return;
        }
        if (extension === 'pdf') {
            state.ready = true; state.pending = null; states.set(row, state);
            ['supplier', 'unit_cost', 'expense_date', 'receipt_reference'].forEach(key => { const fieldHint = hint(row, key); if (fieldHint) fieldHint.textContent = 'Manual entry — PDF is not auto-scanned.'; });
            setRowMessage(row, 'needs-review', 'PDF attached — manual entry', 'Enter the details from the PDF, then confirm every item before saving.');
            updateSubmit();
            return;
        }
        if (!['jpg', 'jpeg', 'png', 'webp'].includes(extension)) {
            setRowMessage(row, 'needs-review', 'Unsupported receipt file', 'Use JPEG, PNG, WebP, PDF, or DOCX. Export HEIC photos as JPEG first.');
            updateSubmit();
            return;
        }
        setRowMessage(row, 'scanning', 'Scanning receipt…', 'Straightening the photo and reading the merchant, total, date, and reference.');
        const controller = new AbortController(); state.pending = controller;
        const timer = setTimeout(() => controller.abort(), 85000);
        let response = null;
        try {
            const body = new FormData(); body.append('receipt', file);
            response = await fetch(form.dataset.scanUrl, { method: 'POST', credentials: 'same-origin', signal: controller.signal, headers: { Accept: 'application/json', 'X-CSRF-TOKEN': form.elements._token.value }, body });
            const result = await response.json().catch(() => ({}));
            if (states.get(row)?.version !== version) return;
            if (!response.ok || !result.scan_id) throw new Error(errorMessage(response, result));
            const incomplete = requiredReceiptFields.some(key => {
                const field = result.fields?.[key];
                return !field?.value || Number(field.confidence ?? 0) < 80;
            });
            if (incomplete) throw new Error(incompleteReceiptMessage);
            row.querySelector('[data-scan-id]').value = result.scan_id;
            setDetectedFields(row, result);
            state.ready = true; state.pending = null; states.set(row, state);
            setRowMessage(row, 'needs-review', 'Scan ready — review this item', ['Check the suggestions against this matching receipt.', ...(result.warnings || [])].join(' '));
        } catch (error) {
            if (states.get(row)?.version !== version) return;
            state.pending = null; states.set(row, state); lastFailedRow = row;
            const message = error.name === 'AbortError' ? 'Scanning timed out. Retake a smaller, clearer photo or retry.' : error.message;
            const rejectedForQuality = /blurry|incomplete|uncropped|merchant, total amount/i.test(message);
            setRowMessage(row, 'needs-review', rejectedForQuality ? 'Receipt is blurry or incomplete' : 'Scan could not finish', message);
            const retryButton = row.querySelector('[data-row-retry]') || byId('soRetryScan');
            if (retryButton) retryButton.hidden = false;
        } finally {
            clearTimeout(timer);
            if (states.get(row)?.version === version) { state.pending = null; states.set(row, state); updateSubmit(); }
        }
        updateSubmit();
    }

    function attachRow(row) {
        if (states.has(row)) return;
        addHints(row);
        states.set(row, { version: 0, pending: null, ready: false });
        const input = fileInput(row);
        input?.addEventListener('change', () => scanRow(row));
        row.querySelector('[data-upload-trigger]')?.addEventListener('click', () => input?.click());
        row.querySelector('[data-row-retry]')?.addEventListener('click', () => scanRow(row));
        row.querySelector('[data-remove-row]')?.addEventListener('click', () => {
            states.get(row)?.pending?.abort();
            row.remove();
            checkbox.checked = false;
            updateSubmit();
        });
        row.querySelectorAll('[data-field]').forEach(field => field.addEventListener('input', () => {
            checkbox.checked = false;
            const key = field.dataset.field;
            const fieldHint = hint(row, key);
            if (fieldHint && ['supplier', 'unit_cost', 'expense_date', 'receipt_reference', 'receipt_type', 'payment_method'].includes(key)) {
                fieldHint.textContent = 'Manually reviewed / edited — confirm before saving.';
                fieldHint.dataset.review = 'true';
            }
            updateSubmit();
        }));
    }

    const resetRestoredForm = () => {
        rows().forEach(row => resetRow(row, true));
        lastFailedRow = null;
        if (checkbox) checkbox.checked = false;
        setGlobalState('', 'Waiting for receipt', 'Upload one receipt for each item. DOCX files are checked before saving.');
        updateSubmit();
    };

    // A browser may restore a file input and the previous DOM state from its
    // back/forward cache. That is not a new upload initiated by the user, so
    // discard the transient receipt and scan state before it can be submitted.
    window.addEventListener?.('pageshow', event => {
        if (event.persisted) resetRestoredForm();
    });

    const firstRow = document.querySelector('[data-expense-row][data-row-index="0"]');
    if (firstRow) {
        attachRow(firstRow);
        // File inputs cannot be restored safely across a new page load. If a
        // browser supplies one anyway, treat it as stale until re-selected.
        if (currentFile(firstRow)) resetRow(firstRow, true);
    }
    byId('soRetryScan')?.addEventListener('click', () => lastFailedRow ? scanRow(lastFailedRow) : undefined);
    checkbox?.addEventListener('change', updateSubmit);
    byId('receiptActivityId')?.addEventListener('change', updateSubmit);
    form.addEventListener('input', event => { if (event.target !== checkbox) checkbox.checked = false; updateSubmit(); });

    byId('soAddExpenseRow')?.addEventListener('click', () => {
        const index = rows().reduce((highest, row) => Math.max(highest, Number(row.dataset.rowIndex) || 0), -1) + 1;
        const key = window.crypto?.randomUUID?.() || `batch-${Date.now()}-${index}`;
        const html = rowTemplate.innerHTML.replaceAll('__INDEX__', String(index)).replaceAll('__NUMBER__', String(index + 1)).replaceAll('__REQUEST_KEY__', key);
        items.insertAdjacentHTML('beforeend', html);
        const row = rows().at(-1);
        attachRow(row);
        row.querySelector('[data-receipt-input]')?.focus();
        row.scrollIntoView({ behavior: 'smooth', block: 'center' });
        updateSubmit();
    });

    form.addEventListener('submit', event => {
        const missing = rows().find(row => {
            const state = states.get(row); const map = fields(row);
            return !state?.ready || state.pending || !map.item_name?.value.trim() || !map.receipt_reference?.value.trim();
        });
        if (missing) {
            event.preventDefault();
            setRowMessage(missing, 'needs-review', 'Finish this item first', 'Attach and validate its matching receipt, then complete the description and reference number.');
            missing.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }
        if (!checkbox?.checked) {
            event.preventDefault();
            review.hidden = false;
            setGlobalState('needs-review', 'Review required', 'Check every item against its matching receipt, then confirm the batch.');
            review.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    });

    form.addEventListener('reset', () => {
        setTimeout(() => {
            rows().slice(1).forEach(row => row.remove());
            const row = rows()[0];
            if (row) { resetRow(row, false); }
            checkbox.checked = false;
            setGlobalState('', 'Waiting for receipt', 'Upload one receipt for each item. DOCX files are checked before saving.');
            updateSubmit();
        }, 0);
    });
    updateSubmit();
})();
