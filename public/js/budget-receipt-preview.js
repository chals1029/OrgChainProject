(function () {
    'use strict';

    const modal = document.getElementById('budgetReceiptPreviewDialog');
    if (!modal) return;
    const body = document.getElementById('budgetReceiptPreviewBody');
    const title = document.getElementById('budgetReceiptPreviewTitle');
    const context = document.getElementById('budgetReceiptPreviewContext');
    const download = document.getElementById('budgetReceiptPreviewDownload');
    const external = document.getElementById('budgetReceiptPreviewExternal');
    const selector = document.getElementById('budgetReceiptPreviewFile');
    const previous = document.getElementById('budgetReceiptPreviewPrevious');
    const next = document.getElementById('budgetReceiptPreviewNext');
    const position = document.getElementById('budgetReceiptPreviewPosition');
    let files = [];
    let index = 0;
    let revision = 0;
    let request = null;
    let mediaUrl = null;
    let opener = null;

    function clearPreview() {
        revision++;
        if (request) request.abort();
        request = null;
        if (mediaUrl) URL.revokeObjectURL(mediaUrl);
        mediaUrl = null;
        body.replaceChildren();
    }

    function message(text) {
        const element = document.createElement('p');
        element.className = 'budget-receipt-message';
        element.setAttribute('role', 'status');
        element.textContent = text;
        body.replaceChildren(element);
    }

    function enableActions(enabled) {
        for (const link of [download, external]) {
            link.setAttribute('aria-disabled', String(!enabled));
            link.tabIndex = enabled ? 0 : -1;
        }
    }

    async function showFile(selected) {
        clearPreview();
        index = selected;
        const file = files[index];
        const token = revision;
        title.textContent = file.name || 'Receipt Preview';
        selector.value = String(index);
        position.textContent = (index + 1) + ' / ' + files.length;
        previous.disabled = index === 0;
        next.disabled = index === files.length - 1;
        download.href = file.downloadUrl;
        download.setAttribute('download', file.name || '');
        external.href = file.receiptUrl;
        enableActions(false);
        message('Loading receipt…');
        const controller = new AbortController();
        request = controller;
        try {
            const response = await fetch(file.receiptUrl, { credentials: 'same-origin', signal: controller.signal });
            if (!response.ok) {
                throw new Error(response.status === 404 ? 'The receipt file is missing.'
                    : response.status === 401 || response.status === 403 ? 'You do not have access to this receipt.'
                    : 'The receipt could not be loaded (' + response.status + ').');
            }
            const blob = await response.blob();
            if (token !== revision) return;
            const mime = blob.type.toLowerCase().split(';')[0];
            const type = (file.name || '').split('.').pop().toLowerCase();
            if (mime === 'text/html') throw new Error('The server did not return a receipt file. Sign in again and reopen the preview.');
            enableActions(true);
            if (mime === 'application/pdf' || type === 'pdf') {
                mediaUrl = URL.createObjectURL(blob);
                const frame = document.createElement('iframe');
                frame.title = file.name || 'Receipt PDF preview';
                frame.src = mediaUrl + '#view=FitH';
                body.replaceChildren(frame);
            } else if (mime.startsWith('image/') || ['jpg', 'jpeg', 'png', 'webp', 'gif'].includes(type)) {
                mediaUrl = URL.createObjectURL(blob);
                const image = document.createElement('img');
                image.alt = file.name || 'Receipt preview';
                image.onerror = function () {
                    if (token === revision) message('This image could not be rendered. Download the original file to inspect it.');
                };
                image.src = mediaUrl;
                body.replaceChildren(image);
            } else if (mime.includes('wordprocessingml') || type === 'docx') {
                if (!window.docx || typeof window.docx.renderAsync !== 'function') {
                    throw new Error('The Word preview library could not be loaded. Download the original file to inspect it.');
                }
                const container = document.createElement('div');
                await window.docx.renderAsync(blob, container, null, {
                    breakPages: true, ignoreWidth: false, ignoreHeight: false,
                    renderHeaders: true, renderFooters: true, renderFootnotes: true, useBase64URL: true,
                });
                if (token !== revision) return;
                body.replaceChildren(container);
            } else {
                message('This file type cannot be previewed in the browser. Download the original file to inspect it.');
            }
        } catch (error) {
            if (token !== revision || error.name === 'AbortError') return;
            message(error.message);
        } finally {
            if (request === controller) request = null;
        }
    }

    window.openBudgetReceiptPreview = function (expense, trigger) {
        files = expense.receiptAttachments?.length ? expense.receiptAttachments : [{
            name: expense.receiptFile, receiptUrl: expense.receiptUrl, downloadUrl: expense.downloadUrl,
        }];
        opener = trigger;
        context.textContent = [expense.desc, expense.date, '₱' + Number(expense.amount || 0).toLocaleString('en-PH', {
            minimumFractionDigits: 2, maximumFractionDigits: 2,
        })].filter(Boolean).join(' · ');
        selector.replaceChildren(...files.map((file, fileIndex) => new Option(file.name || 'Receipt file ' + (fileIndex + 1), String(fileIndex))));
        if (!modal.open) modal.showModal();
        document.body.classList.add('budget-receipt-preview-open');
        showFile(0);
    };

    selector.addEventListener('change', () => showFile(Number(selector.value)));
    previous.addEventListener('click', () => { if (index > 0) showFile(index - 1); });
    next.addEventListener('click', () => { if (index < files.length - 1) showFile(index + 1); });
    document.getElementById('budgetReceiptPreviewClose').addEventListener('click', () => modal.close());
    modal.addEventListener('click', event => {
        if (event.target !== modal) return;
        const bounds = modal.getBoundingClientRect();
        if (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom) modal.close();
    });
    modal.addEventListener('close', () => {
        clearPreview();
        files = [];
        document.body.classList.remove('budget-receipt-preview-open');
        if (opener?.isConnected) opener.focus();
        opener = null;
    });
    for (const link of [download, external]) {
        link.addEventListener('click', event => {
            if (link.getAttribute('aria-disabled') === 'true') event.preventDefault();
        });
    }
})();
