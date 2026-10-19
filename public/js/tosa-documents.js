(function () {
    'use strict';

    var list = document.getElementById('tosaRequirementsList');
    var submitForm = document.getElementById('tosaSubmitForm');
    if (!list || !submitForm) return;

    var submitButton = submitForm.querySelector('button[type="submit"]');
    var submitHelp = document.getElementById('tosaSubmitHelp');
    var submitStatus = document.getElementById('tosaSubmitStatus');
    var submitWrapper = submitForm.querySelector('[data-tosa-submit-wrapper]');
    var submitTooltip = document.getElementById('tosaSubmitTooltip');
    var dialog = document.getElementById('tosaStudentDocViewerModal');
    var entries = [];
    var maxBytes = 25 * 1024 * 1024;
    var submitting = false;
    var revision = submitForm.dataset.tosaRevision === '1';

    function validationMessage(entry) {
        var file = entry.input.files[0];
        if (!file) return entry.saved ? '' : 'Choose a completed file for this section before submitting for review.';
        if (file.size === 0) return 'The selected file is empty. Choose a completed file or cancel this selection.';
        if (file.size > maxBytes) return 'The selected file exceeds 25 MB. Choose a smaller file or cancel this selection.';
        var extension = '.' + file.name.split('.').pop().toLowerCase();
        var allowed = entry.input.accept.toLowerCase().split(',').map(function (value) {
            return value.trim();
        });
        if (allowed.indexOf(extension) === -1) {
            return 'Choose a file in the listed format: ' + entry.input.accept + ', or cancel this selection.';
        }
        return '';
    }

    function updateSubmission() {
        var incomplete = entries.filter(function (entry) {
            return validationMessage(entry) !== '';
        });
        var remaining = incomplete.length;
        submitButton.disabled = submitting || remaining > 0;
        entries.forEach(function (entry) {
            entry.cancel.disabled = submitting;
        });
        if (submitting) {
            submitStatus.textContent = 'Submitting packet for review';
            submitHelp.textContent = 'Saving files and sending your packet to OSO. Please wait.';
        } else if (remaining) {
            submitStatus.textContent = 'Complete your packet before submitting';
            var missing = incomplete.filter(function (entry) { return !entry.input.files[0]; });
            var invalid = incomplete.filter(function (entry) { return Boolean(entry.input.files[0]); });
            submitHelp.textContent = [
                missing.length ? 'Add files for sections ' + missing.map(function (entry) { return entry.code; }).join(', ') + '.' : '',
                invalid.length ? 'Replace invalid files in sections ' + invalid.map(function (entry) { return entry.code; }).join(', ') + '.' : ''
            ].filter(Boolean).join(' ');
        } else {
            submitStatus.textContent = revision ? 'Revision · Ready to resubmit' : 'Package ready for review';
            submitHelp.textContent = revision
                ? 'Review your changes, then resubmit. Your application will lock again.'
                : 'Review your files, then submit the complete packet to OSO.';
        }
        if (submitWrapper) {
            submitWrapper.tabIndex = submitButton.disabled ? 0 : -1;
        }
        if (submitTooltip) {
            submitTooltip.textContent = submitHelp.textContent;
            submitTooltip.hidden = !submitButton.disabled;
        }
    }

    function releasePreview(entry) {
        if (entry.url) {
            if (dialog && dialog.open && dialog.dataset.documentPreviewUrl === entry.url) dialog.close();
            URL.revokeObjectURL(entry.url);
            entry.url = null;
        }
        entry.link.removeAttribute('href');
        entry.link.removeAttribute('data-document-preview-type');
        entry.link.removeAttribute('data-document-preview-download');
        entry.link.removeAttribute('aria-label');
        entry.link.textContent = '';
        entry.link.hidden = true;
    }

    function showFeedback(entry, text) {
        entry.feedback.textContent = text;
        entry.feedback.hidden = !text;
        entry.input.setCustomValidity(text);
    }

    function refreshSelection(entry) {
        releasePreview(entry);
        var file = entry.input.files[0];
        entry.savedFile.hidden = Boolean(file);
        entry.savedLinks.hidden = Boolean(file);
        entry.selection.hidden = !file;
        entry.cancel.hidden = !file;
        entry.pickerText.textContent = file || entry.saved ? 'Replace file' : 'Choose file';
        entry.meta.textContent = file
            ? file.name.split('.').pop().toUpperCase() + ' · ' + (file.size < 1024 * 1024 ? (file.size / 1024).toFixed(1) + ' KB' : (file.size / (1024 * 1024)).toFixed(1) + ' MB')
            : '';
        showFeedback(entry, file ? validationMessage(entry) : '');
        if (file) {
            entry.url = URL.createObjectURL(file);
            entry.link.href = entry.url;
            entry.link.textContent = file.name;
            entry.link.dataset.documentPreviewType = file.name.split('.').pop().toLowerCase();
            entry.link.dataset.documentPreviewDownload = entry.url;
            entry.link.setAttribute('aria-label', 'View ' + file.name);
            entry.link.hidden = false;
        }
        updateSubmission();
    }

    list.querySelectorAll('article[data-tosa-key]').forEach(function (row) {
        var entry = {
            input: row.querySelector('input[type="file"]'),
            saved: row.dataset.tosaSaved === '1',
            savedFile: row.querySelector('[data-tosa-saved-file]'),
            savedLinks: row.querySelector('[data-tosa-saved-links]'),
            selection: row.querySelector('[data-tosa-selection]'),
            link: row.querySelector('[data-tosa-selected-file]'),
            feedback: row.querySelector('[data-tosa-file-feedback]'),
            cancel: row.querySelector('[data-tosa-cancel]'),
            pickerText: row.querySelector('[data-tosa-picker-text]'),
            meta: row.querySelector('[data-tosa-selected-meta]'),
            code: row.dataset.tosaCode,
            url: null,
        };
        entries.push(entry);
        entry.input.addEventListener('change', function () {
            refreshSelection(entry);
        });
        entry.input.addEventListener('invalid', function () {
            var section = entry.input.closest('details');
            if (section) section.open = true;
            showFeedback(entry, validationMessage(entry));
            updateSubmission();
        });
        entry.cancel.addEventListener('click', function () {
            if (submitting) return;
            entry.input.value = '';
            refreshSelection(entry);
            entry.input.focus();
        });
    });
    entries.forEach(refreshSelection);

    submitForm.addEventListener('submit', function (event) {
        if (submitting) {
            event.preventDefault();
            return;
        }
        var firstInvalid = null;
        entries.forEach(function (entry) {
            var error = validationMessage(entry);
            showFeedback(entry, error);
            if (error && !firstInvalid) firstInvalid = entry;
        });
        if (firstInvalid) {
            event.preventDefault();
            updateSubmission();
            firstInvalid.input.reportValidity();
            return;
        }
        if (!window.OrgActionConfirmations.confirmSubmit(event)) return;
        submitting = true;
        submitForm.setAttribute('aria-busy', 'true');
        updateSubmission();
        // Keep the file inputs enabled and intact for the native multipart POST.
    });

    window.addEventListener('pagehide', function () {
        entries.forEach(function (entry) {
            releasePreview(entry);
            if (!submitting) {
                entry.input.value = '';
                entry.savedFile.hidden = false;
                entry.savedLinks.hidden = false;
                entry.selection.hidden = true;
                entry.cancel.hidden = true;
                showFeedback(entry, '');
            }
        });
    });
    window.addEventListener('pageshow', function (event) {
        if (!event.persisted) return;
        submitting = false;
        submitForm.removeAttribute('aria-busy');
        entries.forEach(function (entry) {
            entry.input.value = '';
            refreshSelection(entry);
        });
    });
})();
