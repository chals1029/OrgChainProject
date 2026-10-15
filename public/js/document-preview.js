(function () {
    'use strict';

    var modal = document.querySelector('[data-document-preview-dialog]');
    if (!modal) return;
    var body = modal.querySelector('[data-document-preview-body]');
    var title = modal.querySelector('[data-document-preview-title]');
    var download = modal.querySelector('[data-document-preview-download]');
    var external = modal.querySelector('[data-document-preview-external]');
    var revision = 0;
    var request = null;
    var mediaUrl = null;
    var activeTrigger = null;
    var status = modal.querySelector('[data-document-preview-status]');

    // Keep a single application shell around each format's native renderer.
    modal.className = 'document-preview-dialog';
    body.className = 'document-preview-body';
    body.tabIndex = 0;
    body.setAttribute('aria-label', 'Document contents; scroll to read');
    var box = body.parentElement;
    box.className = 'document-preview-box';
    var head = document.createElement('header');
    head.className = 'document-preview-head';
    var heading = document.createElement('div');
    heading.className = 'document-preview-title';
    if (!status) {
        status = document.createElement('small');
        status.setAttribute('data-document-preview-status', '');
    }
    status.setAttribute('aria-live', 'polite');
    heading.append(title, status);
    head.append(heading);
    var foot = document.createElement('footer');
    foot.className = 'document-preview-actions';
    var close = document.createElement('button');
    close.type = 'button';
    close.className = 'document-preview-action';
    close.setAttribute('data-document-preview-close', '');
    close.textContent = 'Close';
    external.className = 'document-preview-action';
    external.textContent = 'Open in Tab';
    external.target = '_blank';
    external.rel = 'noopener';
    download.className = 'document-preview-action document-preview-action-primary';
    download.textContent = 'Download original';
    foot.append(close, external, download);
    box.replaceChildren(head, body, foot);

    function clearPreview() {
        revision++;
        if (request) request.abort();
        request = null;
        if (mediaUrl) URL.revokeObjectURL(mediaUrl);
        mediaUrl = null;
        external.hidden = true;
        delete modal.dataset.documentPreviewUrl;
        external.removeAttribute('href');
        body.replaceChildren();
    }

    function message(text) {
        var element = document.createElement('p');
        element.className = 'document-preview-message';
        element.setAttribute('role', 'status');
        element.textContent = text;
        body.replaceChildren(element);
    }
    function closePreview() {
        var trigger = activeTrigger;
        activeTrigger = null;
        clearPreview();
        if (modal.open) modal.close();
        if (trigger && trigger.isConnected) trigger.focus();
    }

    window.OfficeDocumentPreview = {
        open: openPreview,
        closeForUrl: function (url) {
            if (modal.dataset.documentPreviewUrl === url) closePreview();
        },
    };

    async function convertDoc(blob, trigger, name, signal) {
        var endpoint = trigger.dataset.documentConversionUrl;
        if (!endpoint) {
            throw new Error('Legacy DOC needs the isolated document converter; no conversion endpoint is available on this page.');
        }
        var csrf = document.querySelector('meta[name="csrf-token"]') || document.querySelector('input[name="_token"]');
        if (!csrf) throw new Error('The security token is missing. Reload this page and try again.');
        var filename = trigger.dataset.documentOriginalName || name || 'document.doc';
        if (!/\.doc$/i.test(filename)) filename = 'document.doc';
        var form = new FormData();
        form.append('file', blob, filename);
        var response = await fetch(endpoint, {
            method: 'POST',
            credentials: 'same-origin',
            signal: signal,
            headers: { 'X-CSRF-TOKEN': csrf.content || csrf.value, Accept: 'application/json' },
            body: form,
        });
        if (!response.ok) {
            var errorMessage = 'DOC conversion failed (' + response.status + '). Reload this page or ask the administrator to check the document converter.';
            if ((response.headers.get('Content-Type') || '').indexOf('application/json') !== -1) {
                var payload = await response.json();
                if (typeof payload.message === 'string') errorMessage = payload.message;
            }
            throw new Error(errorMessage);
        }
        return response.blob();
    }

    async function openPreview(trigger) {
        var isFile = trigger.hasAttribute('data-document-file');
        var url = isFile ? trigger.href : trigger.dataset.documentPreviewUrl;
        if (!url) return false;
        clearPreview();
        activeTrigger = trigger;
        var token = revision;
        modal.dataset.documentPreviewUrl = url;
        var name = isFile ? trigger.textContent.trim() : trigger.dataset.documentPreviewTitle;
        var type = (trigger.dataset.documentPreviewType || '').toLowerCase();
        title.textContent = name || 'Document Preview';
        download.href = trigger.dataset.documentPreviewDownload || url;
        download.setAttribute('download', trigger.dataset.documentOriginalName || (url.startsWith('blob:') && isFile ? name : ''));
        external.href = url;
        external.hidden = false;
        message('Loading document…');
        if (status) status.textContent = 'Loading the original document…';
        if (!modal.open) modal.showModal();
        var controller = new AbortController();
        request = controller;
        try {
            var response = await fetch(url, { credentials: 'same-origin', signal: controller.signal });
            if (!response.ok) throw new Error('The document could not be loaded (' + response.status + ').');
            var blob = await response.blob();
            if (token !== revision) return false;
            var mime = blob.type.toLowerCase().split(';')[0].trim();
            if (mime === 'text/html' || mime === 'application/xhtml+xml' || mime === 'application/json' || mime.endsWith('+json')) {
                throw new Error('The server returned a sign-in page or an error instead of the document.');
            }
            var genericMime = !mime || mime === 'application/octet-stream' || mime === 'binary/octet-stream';
            var isPdf = mime === 'application/pdf' || (genericMime && type === 'pdf');
            var isImage = mime.startsWith('image/') || (genericMime && ['png', 'jpg', 'jpeg', 'gif', 'webp', 'bmp', 'svg', 'avif'].indexOf(type) !== -1);
            var isDocx = mime === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                || ((genericMime || mime === 'application/zip' || mime === 'application/x-zip-compressed') && type === 'docx');
            var isLegacyDoc = !isDocx && (type === 'doc' || mime === 'application/msword' || mime === 'application/vnd.ms-word');
            var converted = false;
            if (isLegacyDoc) {
                message('Converting the original DOC in the isolated document converter…');
                if (status) status.textContent = 'Converting the original DOC bytes…';
                blob = await convertDoc(blob, trigger, name, controller.signal);
                if (token !== revision) return false;
                isPdf = true;
                converted = true;
            }
            if (isPdf) {
                var prefix = await blob.slice(0, 1024).text();
                if (token !== revision) return false;
                if (prefix.indexOf('%PDF-') === -1) {
                    throw new Error('The response is not a PDF document.');
                }
                mediaUrl = URL.createObjectURL(blob.type === 'application/pdf' ? blob : new Blob([blob], { type: 'application/pdf' }));
                external.href = converted ? mediaUrl : url;
                external.hidden = false;
                var frame = document.createElement('iframe');
                frame.title = name || 'PDF document preview';
                frame.src = mediaUrl + '#view=FitH';
                body.replaceChildren(frame);
            } else if (isImage) {
                external.href = url;
                external.hidden = false;
                mediaUrl = URL.createObjectURL(blob);
                var image = document.createElement('img');
                image.alt = name || 'Document preview';
                image.src = mediaUrl;
                await image.decode();
                if (token !== revision) return false;
                body.replaceChildren(image);
            } else if (isDocx) {
                if (!window.docx || typeof window.docx.renderAsync !== 'function') {
                    throw new Error('The Word preview library could not be loaded.');
                }
                var container = document.createElement('div');
                await window.docx.renderAsync(blob, container, null, {
                    breakPages: true,
                    ignoreWidth: false,
                    ignoreHeight: false,
                    renderHeaders: true,
                    renderFooters: true,
                    renderFootnotes: true,
                    renderEndnotes: true,
                    useBase64URL: true,
                });
                if (token !== revision) return false;
                body.replaceChildren(container);
            } else {
                message('This file type cannot be previewed in the browser. Use Download to open the original document.');
                if (status) status.textContent = 'No browser preview is available for this file type.';
                return false;
            }
            if (status) status.textContent = converted
                ? 'DOC · PDF preview'
                : (isPdf ? 'PDF · Native browser viewer' : (isDocx ? 'Word document preview' : 'Document preview'));
            body.scrollTop = 0;
            body.scrollLeft = 0;
            return true;
        } catch (error) {
            if (token !== revision || error.name === 'AbortError') return false;
            message(error.message + ' Use Download to open the original document.');
            if (status) status.textContent = 'The original document could not be previewed.';
            return false;
        } finally {
            if (request === controller) request = null;
        }
    }

    document.addEventListener('click', function (event) {
        var trigger = event.target.closest('[data-document-preview], [data-document-file]');
        if (trigger && !event.ctrlKey && !event.metaKey && !event.shiftKey && !event.altKey) {
            event.preventDefault();
            openPreview(trigger);
        } else if (event.target.closest('[data-document-preview-close]')) {
            closePreview();
        }
    });
    modal.addEventListener('click', function (event) {
        if (event.target === modal) closePreview();
    });
    modal.addEventListener('close', function () {
        if (modal.open) return;
        var trigger = activeTrigger;
        activeTrigger = null;
        clearPreview();
        if (trigger && trigger.isConnected) trigger.focus();
    });
    modal.addEventListener('cancel', function (event) {
        event.preventDefault();
        closePreview();
    });
})();
