(function () {
    'use strict';

    var form = document.getElementById('soRenewalSubmissionForm');
    if (!form || form.dataset.canSubmit !== '1') return;

    var submit = document.getElementById('soRenewalSubmit');
    var inputs = Array.from(document.querySelectorAll('[data-so-renewal-document]'));
    var count = document.getElementById('soRenewalCount');
    var progress = document.getElementById('soRenewalProgress');
    var remaining = document.getElementById('soRenewalRemaining');
    var help = document.getElementById('soRenewalHelp');
    var objectUrls = new Map();
    var extensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'png', 'jpg', 'jpeg'];

    var update = function () {
        var complete = 0;
        inputs.forEach(function (input) {
            var file = input.files[0];
            var uploaded = input.dataset.uploaded === '1';
            var row = input.closest('.so-renewal-row');
            var status = row.querySelector('[data-so-file-status]');
            var link = row.querySelector('[data-so-file-link]');
            var saved = row.querySelector('[data-so-saved-file]');
            var label = row.querySelector('[data-so-upload-label]');
            var error = '';
            if (file) {
                var extension = file.name.split('.').pop().toLowerCase();
                if (extensions.indexOf(extension) === -1) error = 'Choose a PDF, Office document, PNG or JPG.';
                else if (!file.size) error = 'This file is empty.';
                else if (file.size > 20 * 1024 * 1024) error = 'Maximum file size is 20 MB.';
            }
            input.setCustomValidity(error);
            var ready = !error && (Boolean(file) || uploaded);
            if (ready) complete++;
            status.textContent = error || 'No file selected.';
            status.hidden = ready;
            link.textContent = file ? file.name : link.dataset.savedName;
            link.hidden = !ready;
            if (ready) {
                link.href = file ? objectUrls.get(input) : link.dataset.savedUrl;
                link.dataset.renewalPreviewType = file ? extension : link.dataset.savedType;
                link.dataset.renewalPreviewDownload = file ? link.href : link.dataset.savedDownload;
                link.setAttribute('aria-label', 'Open ' + link.textContent);
            } else {
                link.removeAttribute('href');
                link.removeAttribute('aria-label');
            }
            status.classList.toggle('is-selected', ready);
            status.classList.toggle('is-error', Boolean(error));
            if (saved) saved.hidden = Boolean(file);
            label.textContent = file || uploaded ? 'Replace File' : 'Upload';
        });
        var total = inputs.length;
        var missing = total - complete;
        count.textContent = complete + '/' + total + ' ready';
        count.classList.toggle('ok', missing === 0);
        count.classList.toggle('wait', missing !== 0);
        progress.setAttribute('aria-valuenow', complete);
        progress.querySelector('span').style.width = (total ? 100 * complete / total : 0) + '%';
        remaining.textContent = missing ? missing + ' requirement' + (missing === 1 ? '' : 's') + ' remaining' : 'All required documents ready';
        help.textContent = 'Files are not uploaded and details are not saved until you submit for review.';
        var detailsComplete = Array.from(form.querySelectorAll('[data-so-renewal-editable]')).every(function (input) { return input.value.trim() !== ''; });
        submit.disabled = missing > 0 || !detailsComplete || !form.checkValidity();
        return !submit.disabled;
    };

    inputs.forEach(function (input) {
        var row = input.closest('.so-renewal-row');
        row.querySelector('[data-so-renewal-upload-button]').addEventListener('click', function () { input.click(); });
        input.addEventListener('change', function () {
            var oldUrl = objectUrls.get(input);
            if (oldUrl) URL.revokeObjectURL(oldUrl);
            objectUrls.delete(input);
            if (input.files[0]) objectUrls.set(input, URL.createObjectURL(input.files[0]));
            update();
        });
    });
    form.addEventListener('input', update);
    document.getElementById('soRenewalEditInfo').addEventListener('click', function () {
        form.querySelectorAll('[data-so-renewal-editable]').forEach(function (input) { input.readOnly = false; });
        form.querySelector('[data-so-renewal-editable]').focus();
    });
    form.addEventListener('submit', function (event) {
        if (event.submitter !== submit || !update()) {
            event.preventDefault();
            return;
        }
        submit.disabled = true;
        submit.setAttribute('aria-busy', 'true');
        help.textContent = 'Submitting your organization details and documents for review…';
    });
    update();
})();
