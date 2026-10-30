(() => {
    'use strict';

    const form = document.getElementById('announcementComposerForm');
    if (!form) return;

    const type = document.getElementById('postTypeSelect');
    const priority = document.getElementById('postPrioritySelect');
    const body = document.getElementById('postBody');
    const expiry = document.getElementById('postExpiresAt');
    const fileInput = document.getElementById('postFileInput');
    const button = document.getElementById('publishSubmitBtn');
    const fileStatus = document.getElementById('announcementAttachmentStatus');
    const expiryStatus = document.getElementById('announcementExpiryStatus');
    const offset = form.dataset.timezoneOffset;
    const offsetMinutes = (Number(offset.slice(1, 3)) * 60 + Number(offset.slice(4, 6))) * (offset[0] === '-' ? -1 : 1);
    const serverStart = Date.parse(form.dataset.serverNow);
    const clockStart = performance.now();
    const expiryGuidance = expiryStatus.textContent;
    const extensions = new Set(fileInput.accept.split(',').map(value => value.trim().toLowerCase()));
    const maxBytes = Number(fileInput.dataset.maxSize) * 1024;
    let expiryTimer;

    function refresh() {
        clearTimeout(expiryTimer);
        const now = serverStart + performance.now() - clockStart;
        const expiresAt = expiry.value ? Date.parse(`${expiry.value}:00${offset}`) : NaN;
        const expiryError = expiry.value && (!Number.isFinite(expiresAt) || expiresAt <= now)
            ? 'Choose an expiration date and time in the future.' : '';
        expiry.min = new Date(now + offsetMinutes * 60000).toISOString().slice(0, 16);
        expiry.setCustomValidity(expiryError);
        expiryStatus.textContent = expiryError || expiryGuidance;
        body.setCustomValidity(body.value.trim() ? '' : 'Enter announcement details.');

        let file = fileInput.files[0];
        if (file) {
            const error = file.size === 0
                ? 'The attachment is empty and cannot be uploaded. Choose a non-empty file.'
                : file.size > maxBytes
                    ? 'The attachment exceeds the 15 MB limit and cannot be uploaded. Choose a smaller file.'
                    : !extensions.has(`.${file.name.split('.').pop().toLowerCase()}`)
                        ? 'This file type cannot be uploaded. Choose a PDF, Word, Excel or supported image file.' : '';
            if (error) {
                fileInput.value = '';
                file = null;
                window.alert(error);
            }
        }
        document.getElementById('attachedFileName').textContent = file?.name || '';
        document.getElementById('attachedFileSize').textContent = file ? `(${window.formatBytes(file.size)})` : '';
        document.getElementById('attachedFileContainer').style.display = file ? 'block' : 'none';
        document.getElementById('attachButtonLabel').textContent = file ? 'Replace File' : 'Attach Supporting File';
        fileInput.setCustomValidity('');
        fileInput.dataset.uploadInvalid = 'false';
        fileStatus.textContent = file ? 'Attachment selected. It will upload only when published.' : 'Optional attachment.';
        fileStatus.classList.remove('is-error');
        fileStatus.classList.toggle('is-selected', Boolean(file));

        const valid = type.value !== '' && priority.value !== '' && body.value.trim().length > 0
            && body.value.length <= 10000 && Number.isFinite(expiresAt) && expiresAt > now
            && form.checkValidity();
        button.disabled = !valid || form.dataset.orgUploadSubmitting === 'true';
        if (Number.isFinite(expiresAt) && expiresAt > now) {
            expiryTimer = setTimeout(refresh, Math.min(expiresAt - now + 10, 2147483647));
        }
        return valid;
    }

    window.removeAttachedFile = () => {
        fileInput.value = '';
        fileInput.dispatchEvent(new Event('change', { bubbles: true }));
    };
    window.resetComposerForm = () => {
        form.reset();
        type.value = 'General Announcement';
        priority.value = 'normal';
        body.value = '';
        expiry.value = '';
        fileInput.value = '';
        window.updateComposerTypePreview(type.value);
        window.updateComposerPriorityPreview(priority.value);
        refresh();
    };

    form.addEventListener('input', refresh);
    // Run after the shared document-level upload handler has updated validity.
    window.addEventListener('change', event => {
        if (form.contains(event.target)) refresh();
    });
    form.addEventListener('reset', () => {
        queueMicrotask(refresh);
    });
    form.addEventListener('submit', event => {
        if (!refresh()) {
            event.preventDefault();
            event.stopImmediatePropagation();
        }
    });
    window.addEventListener('pageshow', refresh);
    window.updateComposerTypePreview(type.value);
    window.updateComposerPriorityPreview(priority.value);
    refresh();
})();
