(function () {
    'use strict';

    var modal = document.getElementById('renewalDocViewerModal');
    if (!modal) return;
    var body = document.getElementById('renewalDocViewerBody');
    var title = document.getElementById('renewalDocViewerTitle');
    var download = document.getElementById('renewalDocViewerDownloadBtn');
    var external = document.getElementById('renewalDocViewerNewTabBtn');
    var revision = 0;
    var request = null;
    var mediaUrl = null;

    function clearPreview() {
        revision++;
        if (request) request.abort();
        request = null;
        if (mediaUrl) URL.revokeObjectURL(mediaUrl);
        mediaUrl = null;
        body.replaceChildren();
    }

    function message(text) {
        var element = document.createElement('p');
        element.className = 'rn-preview-message';
        element.setAttribute('role', 'status');
        element.textContent = text;
        body.replaceChildren(element);
    }

    async function openPreview(trigger) {
        var isFile = trigger.hasAttribute('data-so-file-link');
        var url = isFile ? trigger.href : trigger.dataset.renewalPreviewUrl;
        if (!url) return;
        clearPreview();
        var token = revision;
        var name = isFile ? trigger.textContent.trim() : trigger.dataset.renewalPreviewTitle;
        var type = (trigger.dataset.renewalPreviewType || '').toLowerCase();
        title.textContent = name || 'Document Preview';
        download.href = trigger.dataset.renewalPreviewDownload || url;
        download.setAttribute('download', url.startsWith('blob:') && isFile ? name : '');
        external.href = url;
        message('Loading document…');
        if (!modal.open) modal.showModal();
        var controller = new AbortController();
        request = controller;
        try {
            var response = await fetch(url, { credentials: 'same-origin', signal: controller.signal });
            if (!response.ok) throw new Error('The document could not be loaded (' + response.status + ').');
            var blob = await response.blob();
            if (token !== revision) return;
            var mime = blob.type.toLowerCase().split(';')[0];
            if (mime === 'application/pdf' || type === 'pdf') {
                mediaUrl = URL.createObjectURL(blob);
                var frame = document.createElement('iframe');
                frame.id = 'renewalDocViewerIframe';
                frame.title = name || 'PDF document preview';
                frame.src = mediaUrl + '#view=FitH';
                body.replaceChildren(frame);
            } else if (mime.startsWith('image/') || ['png', 'jpg', 'jpeg'].indexOf(type) !== -1) {
                mediaUrl = URL.createObjectURL(blob);
                var image = document.createElement('img');
                image.alt = name || 'Document preview';
                image.src = mediaUrl;
                body.replaceChildren(image);
            } else if (mime.includes('wordprocessingml') || type === 'docx') {
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
                    useBase64URL: true,
                });
                if (token !== revision) return;
                body.replaceChildren(container);
            } else {
                message('This file type cannot be previewed in the browser. Use Download to open the original document.');
            }
        } catch (error) {
            if (token !== revision || error.name === 'AbortError') return;
            message(error.message + ' Use Download to open the original document.');
        } finally {
            if (request === controller) request = null;
        }
    }

    document.addEventListener('click', function (event) {
        var trigger = event.target.closest('[data-renewal-preview], [data-so-file-link]');
        if (trigger && !event.ctrlKey && !event.metaKey && !event.shiftKey && !event.altKey) {
            event.preventDefault();
            openPreview(trigger);
        } else if (event.target.closest('[data-renewal-preview-close]')) {
            modal.close();
        }
    });
    modal.addEventListener('close', clearPreview);
    modal.addEventListener('cancel', clearPreview);
})();
