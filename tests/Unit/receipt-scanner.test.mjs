import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { runInNewContext } from 'node:vm';
import test from 'node:test';

// Small DOM doubles exercise the shipped multi-row script without a browser or network.
const code = readFileSync(new URL('../../public/js/receipt-scanner.js', import.meta.url), 'utf8');

class Element {
    constructor(id = '', tagName = 'INPUT', dataset = {}) {
        Object.assign(this, {
            id, tagName, value: '', hidden: false, checked: false, disabled: false,
            textContent: '', innerHTML: '', name: '', dataset, events: {}, files: [],
            children: [], attributes: {}, removed: false,
        });
    }

    addEventListener(name, action) { (this.events[name] ??= []).push(action); }

    fire(name, target = this) {
        return Promise.all((this.events[name] || []).map(fn => fn({
            target,
            preventDefault() { this.prevented = true; },
        })));
    }

    setAttribute(name, value) { this.attributes[name] = value; this[name] = value; }
    removeAttribute(name) { delete this.attributes[name]; delete this[name]; }
    appendChild(node) { this.children.push(node); return node; }
    insertAdjacentElement(where, node) { this.children.push(node); node.parentNode = this; this.hint = node; return node; }
    remove() { this.removed = true; if (this.parentNode) this.parentNode.children = this.parentNode.children.filter(child => child !== this); }
    focus() {}
    click() {}
    scrollIntoView() {}

    matches(selector) {
        if (selector === 'input') return this.tagName === 'INPUT';
        if (selector === '[data-field]') return Boolean(this.dataset.field);
        if (selector === '[data-scan-id]') return Boolean(this.dataset.scanId !== undefined);
        if (selector === '[data-receipt-input]') return Boolean(this.dataset.receiptInput !== undefined);
        if (selector === '[data-row-status]') return Boolean(this.dataset.rowStatus !== undefined);
        if (selector === '[data-upload-trigger]') return Boolean(this.dataset.uploadTrigger !== undefined);
        if (selector === '[data-row-retry]') return Boolean(this.dataset.rowRetry !== undefined);
        if (selector === '[data-remove-row]') return Boolean(this.dataset.removeRow !== undefined);
        if (selector === '[data-preview]') return Boolean(this.dataset.preview !== undefined);
        if (selector === '[data-preview-img]') return Boolean(this.dataset.previewImg !== undefined);
        if (selector === '[data-preview-name]') return Boolean(this.dataset.previewName !== undefined);
        if (selector === '[data-upload-status]') return Boolean(this.dataset.uploadStatus !== undefined);
        if (selector === '[type="submit"]') return this.type === 'submit';
        const field = selector.match(/^\[data-field="([^"]+)"\]$/);
        if (field) return this.dataset.field === field[1];
        const confidence = selector.match(/^\[data-confidence-for="([^"]+)"\]$/);
        if (confidence) return this.dataset.confidenceFor === confidence[1];
        const rowIndex = selector.match(/^\[data-expense-row\]\[data-row-index="(\d+)"\]$/);
        if (rowIndex) return this.dataset.expenseRow !== undefined && this.dataset.rowIndex === rowIndex[1];
        return false;
    }

    querySelector(selector) {
        if (selector === 'input' && this.id === 'soReviewCheck') return this.reviewInput;
        if (selector === '[type="submit"]' && this.id === 'soExpenseForm') return this.submit;
        return this.querySelectorAll(selector)[0] || null;
    }

    querySelectorAll(selector) {
        return this.children.flatMap(child => [
            ...(child.matches(selector) ? [child] : []),
            ...child.querySelectorAll(selector),
        ]);
    }
}

function setup(fetcher, restoredFile = null) {
    const ids = [
        'soExpenseForm', 'soReceiptInput', 'soReceiptScanId', 'soReviewCheck', 'soRetryScan',
        'soSupplier', 'soUnitCost', 'soExpenseDate', 'soReceiptReference', 'soReceiptType',
        'soPaymentMethod', 'soOcrStatus', 'soOcrTitle', 'soOcrText', 'soReceiptUploadStatus',
        'receiptActivityId', 'soItemName', 'soQuantity', 'soReceiptPreview', 'soReceiptPreviewImg',
        'soReceiptPreviewName', 'soExpenseItems', 'soExpenseRowTemplate',
    ];
    const elements = Object.fromEntries(ids.map(id => [id, new Element(id)]));
    const checkbox = new Element('confirm');
    const submit = new Element('submit'); submit.type = 'submit'; submit.disabled = true;
    const form = elements.soExpenseForm;
    form.dataset.scanUrl = '/scan';
    form.dataset.validateDocumentUrl = '/validate-document';
    form.elements = { _token: { value: 'synthetic-csrf' } };
    form.submit = submit;
    elements.receiptActivityId.value = 'activity-1';
    elements.soReviewCheck.reviewInput = checkbox;

    elements.soReceiptType.tagName = elements.soPaymentMethod.tagName = 'SELECT';
    elements.soReceiptType.value = 'unknown'; elements.soPaymentMethod.value = 'unknown';

    const row = new Element('row0', 'ARTICLE', { expenseRow: '', rowIndex: '0' });
    const fieldDefinitions = [
        ['request_key', 'hidden'], ['item_name', 'INPUT'], ['category', 'SELECT'], ['quantity', 'INPUT'],
        ['unit_cost', 'INPUT'], ['expense_date', 'INPUT'], ['supplier', 'INPUT'],
        ['receipt_reference', 'INPUT'], ['receipt_type', 'SELECT'], ['payment_method', 'SELECT'],
    ];
    const fields = {};
    for (const [key, tagName] of fieldDefinitions) {
        const field = new Element(`${key}-0`, tagName, { field: key });
        field.name = `expenses[0][${key}]`;
        if (key === 'quantity') field.value = '1';
        if (key === 'receipt_type' || key === 'payment_method') field.value = 'unknown';
        fields[key] = field; row.appendChild(field);
    }
    elements.soReceiptScanId.dataset.scanId = '';
    row.appendChild(elements.soReceiptScanId);

    form.appendChild(submit);
    elements.soExpenseItems.appendChild(row);
    elements.soReceiptInput.dataset.receiptInput = '';
    elements.soReceiptInput.files = restoredFile ? [restoredFile] : [];
    elements.soReceiptInput.parentNode = row;
    elements.soReceiptScanId.parentNode = row;
    // The first row uses the legacy IDs, while its fields are also available to the row selectors.
    for (const [key, field] of Object.entries(fields)) {
        const legacyId = {
            item_name: 'soItemName', quantity: 'soQuantity', unit_cost: 'soUnitCost', expense_date: 'soExpenseDate',
            supplier: 'soSupplier', receipt_reference: 'soReceiptReference', receipt_type: 'soReceiptType',
            payment_method: 'soPaymentMethod',
        }[key];
        const legacy = legacyId ? elements[legacyId] : null;
        if (legacy) {
            legacy.dataset.field = key; legacy.name = field.name;
            row.children[row.children.indexOf(field)] = legacy;
        }
    }
    row.children.find(child => child.dataset.scanId !== undefined).id = 'soReceiptScanId';

    const document = {
        getElementById: id => elements[id],
        querySelector: selector => selector === '[data-expense-row][data-row-index="0"]' ? row : null,
        querySelectorAll: selector => selector === '[data-expense-row]' ? [row] : [],
        createElement: tag => new Element('', tag.toUpperCase()),
    };
    const windowEvents = {};
    runInNewContext(code, {
        document,
        window: {
            crypto: { randomUUID: () => 'synthetic-row-key' },
            addEventListener(name, action) { (windowEvents[name] ??= []).push(action); },
        },
        fetch: fetcher,
        AbortController,
        FormData: class { append() {} },
        URL: { createObjectURL: () => 'blob:test', revokeObjectURL() {} },
        setTimeout,
        clearTimeout,
        console,
    });
    return {
        ...elements,
        checkbox,
        submit,
        row,
        async upload(file) { elements.soReceiptInput.files = [file]; await elements.soReceiptInput.fire('change'); },
        async pageshow(persisted = true) { return Promise.all((windowEvents.pageshow || []).map(fn => fn({ persisted }))); },
    };
}

const photo = { name: 'SYNTHETIC.png', size: 25000, type: 'image/png' };
const result = (id = 'scan-1', amount = '300.00') => ({
    ok: true,
    json: async () => ({
        scan_id: id,
        fields: {
            supplier: { value: 'Savemore Market', confidence: 90 },
            unit_cost: { value: amount, confidence: 92 },
            expense_date: { value: '2026-09-20', confidence: 90 },
            receipt_reference: { value: '123456789', confidence: 90 },
            receipt_type: { value: 'paper_receipt', confidence: 90 },
            payment_method: { value: 'cash', confidence: 90 },
        },
        warnings: [],
    }),
});

test('restored browser receipt state is cleared until the user selects a file again', async () => {
    const ui = setup(async () => result(), photo);
    assert.equal(ui.soReceiptUploadStatus.textContent, 'No receipt selected.');
    assert.equal(ui.soReceiptPreview.hidden, true);
    assert.match(ui.soOcrTitle.textContent, /Waiting for receipt/);
    assert.equal(ui.submit.disabled, true);

    await ui.upload(photo);
    assert.match(ui.soOcrTitle.textContent, /Scan ready/);
    await ui.pageshow();
    assert.equal(ui.soReceiptUploadStatus.textContent, 'No receipt selected.');
    assert.equal(ui.soReceiptPreview.hidden, true);
    assert.match(ui.soOcrTitle.textContent, /Waiting for receipt/);
    assert.equal(ui.soReceiptScanId.value, '');
    assert.equal(ui.submit.disabled, true);
});

test('results require confirmation and later edits clear confirmation', async () => {
    const ui = setup(async () => result());
    assert.equal(ui.submit.disabled, true);
    await ui.upload(photo);
    assert.equal(ui.soUnitCost.value, '300.00');
    assert.equal(ui.soItemName.value, '');
    assert.equal(ui.submit.disabled, true);
    ui.checkbox.checked = true; await ui.checkbox.fire('change');
    assert.equal(ui.submit.disabled, false);
    ui.soUnitCost.value = '250'; await ui.soUnitCost.fire('input'); await ui.soExpenseForm.fire('input', ui.soUnitCost);
    assert.equal(ui.checkbox.checked, false);
    assert.equal(ui.submit.disabled, true);
    assert.match(ui.soUnitCost.hint.textContent, /Manually reviewed/);
});

test('changing photo clears old fields immediately and ignores a late response', async () => {
    const responses = [];
    const ui = setup(() => new Promise(resolve => responses.push(resolve)));
    ui.soSupplier.value = 'Old vendor'; ui.checkbox.checked = true;
    const first = ui.upload(photo);
    assert.equal(ui.soSupplier.value, '');
    assert.equal(ui.checkbox.checked, false);
    const second = ui.upload({ ...photo, name: 'new.png' });
    responses[1](result('new-scan', '200.00')); await second;
    responses[0](result('old-scan', '999.00')); await first;
    assert.equal(ui.soReceiptScanId.value, 'new-scan');
    assert.equal(ui.soUnitCost.value, '200.00');
});

test('reset aborts a scan and late results cannot restore it', async () => {
    let resolve;
    const ui = setup(() => new Promise(r => { resolve = r; }));
    const scan = ui.upload(photo);
    await ui.soExpenseForm.fire('reset');
    resolve(result()); await scan;
    await new Promise(r => setTimeout(r, 5));
    assert.equal(ui.soReceiptScanId.value, '');
    assert.equal(ui.submit.disabled, true);
    assert.equal(ui.soReceiptPreview.hidden, true);
});

test('503 offers retry and never permits saving without a valid scan', async () => {
    let attempts = 0;
    const ui = setup(async () => ++attempts === 1
        ? { ok: false, status: 503, json: async () => ({ message: 'Scanner unavailable' }) }
        : result());
    await ui.upload(photo);
    assert.equal(ui.soRetryScan.hidden, false);
    assert.equal(ui.submit.disabled, true);
    await ui.soRetryScan.fire('click');
    assert.equal(ui.soReceiptScanId.value, 'scan-1');
    assert.equal(ui.submit.disabled, true);
});

test('PDF is explicitly manual and unsupported photos cannot bypass scanning', async () => {
    const ui = setup(() => { throw new Error('Should not call OCR'); });
    await ui.upload({ ...photo, name: 'receipt.pdf', type: 'application/pdf' });
    assert.match(ui.soOcrTitle.textContent, /manual entry/);
    assert.equal(ui.soReceiptScanId.value, '');
    ui.checkbox.checked = true; await ui.checkbox.fire('change');
    assert.equal(ui.submit.disabled, false);
    await ui.upload({ ...photo, name: 'receipt.heic', type: 'image/heic' });
    assert.match(ui.soOcrTitle.textContent, /Unsupported/);
    assert.equal(ui.submit.disabled, true);
    assert.equal(ui.soReviewCheck.hidden, true);
});

test('filename is text and incomplete scans are blocked before fields are accepted', async () => {
    const response = result();
    response.json = async () => ({ scan_id: 'partial', fields: { unit_cost: { value: '200.00', confidence: 55 } }, warnings: ['Check the total.'] });
    const ui = setup(async () => response);
    await ui.upload({ ...photo, name: '<img src=x onerror=alert(1)>.png' });
    assert.match(ui.soReceiptPreviewName.textContent, /^<img/);
    assert.equal(ui.soSupplier.value, '');
    assert.equal(ui.soExpenseDate.value, '');
    assert.equal(ui.soUnitCost.value, '');
    assert.equal(ui.soReceiptScanId.value, '');
    assert.match(ui.soOcrTitle.textContent, /blurry or incomplete/);
    assert.equal(ui.submit.disabled, true);
});
