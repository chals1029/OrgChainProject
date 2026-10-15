(function () {
    'use strict';

    var form = document.getElementById('soRenewalSubmissionForm');
    if (!form || form.dataset.canSubmit !== '1') return;

    var submit = document.getElementById('soRenewalSubmit');
    var inputs = Array.from(document.querySelectorAll('[data-so-renewal-document]'));
    var details = Array.from(form.querySelectorAll('[data-so-renewal-editable]'));
    var edit = document.getElementById('soRenewalEditInfo');
    var save = document.getElementById('soRenewalSaveInfo');
    var informationStatus = document.getElementById('soRenewalInformationStatus');
    var count = document.getElementById('soRenewalCount');
    var progress = document.getElementById('soRenewalProgress');
    var remaining = document.getElementById('soRenewalRemaining');
    var help = document.getElementById('soRenewalHelp');
    var objectUrls = new Map();
    var saving = false;
    var savedNames = details.map(function (input) { return input.value; });
    var extensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'png', 'jpg', 'jpeg'];

    function fileError(file) {
        if (!file) return '';
        if (extensions.indexOf(file.name.split('.').pop().toLowerCase()) === -1) return 'Choose a PDF, Office document, PNG or JPG.';
        if (!file.size) return 'This file is empty.';
        if (file.size > 20 * 1024 * 1024) return 'Maximum file size is 20 MB.';
        return '';
    }

    function releasePreview(input) {
        var url = objectUrls.get(input);
        if (!url) return;
        if (window.OfficeDocumentPreview) window.OfficeDocumentPreview.closeForUrl(url);
        URL.revokeObjectURL(url);
        objectUrls.delete(input);
    }

    function update() {
        var complete = 0;
        var submitting = form.dataset.submitting === '1';
        inputs.forEach(function (input) {
            var file = input.files[0];
            var uploaded = input.dataset.uploaded === '1';
            var row = input.closest('.so-renewal-row');
            var status = row.querySelector('[data-so-file-status]');
            var link = row.querySelector('[data-so-file-link]');
            var saved = row.querySelector('[data-so-saved-file]');
            var label = row.querySelector('[data-so-upload-label]');
            var error = fileError(file);
            var selected = Boolean(file) && !error;
            input.setCustomValidity(error);
            var ready = selected || uploaded;
            if (ready) complete++;
            row.classList.toggle('is-ready', ready);
            var icon = row.querySelector('[data-so-readiness-icon]');
            icon.classList.toggle('bi-check-circle-fill', ready);
            icon.classList.toggle('bi-file-earmark-text', !ready);
            status.textContent = error || 'No file selected.';
            status.hidden = ready && !error;
            status.classList.toggle('is-error', Boolean(error));
            link.textContent = selected ? file.name : link.dataset.savedName;
            link.hidden = !ready;
            if (ready) {
                if (selected && !objectUrls.has(input)) objectUrls.set(input, URL.createObjectURL(file));
                link.href = selected ? objectUrls.get(input) : link.dataset.savedUrl;
                link.dataset.documentPreviewType = selected ? file.name.split('.').pop().toLowerCase() : link.dataset.savedType;
                link.dataset.documentPreviewDownload = selected ? link.href : link.dataset.savedDownload;
                link.setAttribute('aria-label', 'Open ' + link.textContent);
            } else {
                link.removeAttribute('href');
                link.removeAttribute('aria-label');
            }
            if (saved) saved.hidden = selected;
            label.textContent = file || uploaded ? 'Replace File' : 'Upload';
            row.querySelector('[data-so-renewal-upload-button]').disabled = submitting;
        });
        var total = inputs.length;
        var missing = total - complete;
        count.textContent = complete + '/' + total + ' ready';
        count.classList.toggle('ok', missing === 0);
        count.classList.toggle('wait', missing !== 0);
        progress.setAttribute('aria-valuenow', complete);
        progress.querySelector('span').style.width = (total ? 100 * complete / total : 0) + '%';
        remaining.textContent = missing ? missing + ' requirement' + (missing === 1 ? '' : 's') + ' remaining' : 'All required documents ready';
        help.textContent = submitting ? 'Submitting your organization details and documents for review…' : 'Files are uploaded only when you submit for review. Save changes records names only.';
        var detailsComplete = details.every(function (input) { return input.value.trim() !== ''; });
        var changed = details.some(function (input, index) { return input.value !== savedNames[index]; });
        save.disabled = submitting || saving || !detailsComplete || !changed;
        edit.disabled = submitting || saving;
        submit.disabled = submitting || saving || total === 0 || missing > 0 || !detailsComplete || !form.checkValidity();
        return !submit.disabled;
    }

    inputs.forEach(function (input) {
        input.closest('.so-renewal-row').querySelector('[data-so-renewal-upload-button]').addEventListener('click', function () {
            if (form.dataset.submitting !== '1') input.click();
        });
        input.addEventListener('change', function () {
            releasePreview(input);
            update();
        });
    });
    form.addEventListener('input', update);
    edit.addEventListener('click', function () {
        if (saving || form.dataset.submitting === '1') return;
        details.forEach(function (input) { input.readOnly = false; });
        informationStatus.textContent = 'Edit the adviser or dean name, then click Save changes.';
        details[0].focus();
    });
    save.addEventListener('click', async function () {
        if (saving || form.dataset.submitting === '1' || save.disabled) return;
        if (!details.every(function (input) { return input.reportValidity(); })) return;
        if (!window.OrgActionConfirmations.confirm('Save these organization information changes?')) return;
        var payload = {
            window_id: form.elements.window_id.value,
            organization_name: form.elements.organization_name.value
        };
        details.forEach(function (input) { payload[input.name] = input.value; });
        var readOnlyBefore = details.map(function (input) { return input.readOnly; });
        saving = true;
        details.forEach(function (input) { input.readOnly = true; });
        save.setAttribute('aria-busy', 'true');
        informationStatus.textContent = 'Saving organization information…';
        update();
        try {
            var response = await fetch(form.dataset.informationSaveUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': form.elements._token.value
                },
                body: JSON.stringify(payload)
            });
            var result;
            try {
                result = await response.json();
            } catch (error) {
                throw new Error('The server did not return a save confirmation. Your selected files are still on this page; try again without reloading.');
            }
            if (!response.ok) {
                var messages = Object.values(result.errors || {}).flat();
                throw new Error(messages.join(' ') || result.message || 'Information could not be saved.');
            }
            details.forEach(function (input) {
                input.value = result.information[input.name];
                input.readOnly = true;
            });
            savedNames = details.map(function (input) { return input.value; });
            form.elements.college.value = result.information.college || '';
            informationStatus.classList.remove('err');
            informationStatus.classList.add('ok');
            informationStatus.textContent = result.message;
        } catch (error) {
            details.forEach(function (input, index) { input.readOnly = readOnlyBefore[index]; });
            informationStatus.classList.remove('ok');
            informationStatus.classList.add('err');
            informationStatus.textContent = error.message || 'Information could not be saved. Try again without reloading; your selected files are still on this page.';
        } finally {
            saving = false;
            save.removeAttribute('aria-busy');
            update();
        }
    });
    form.addEventListener('submit', function (event) {
        if (event.submitter !== submit || !update()) {
            event.preventDefault();
            return;
        }
        if (!window.OrgActionConfirmations.confirmSubmit(event, 'Submit this renewal packet for review?')) return;
        form.dataset.submitting = '1';
        submit.setAttribute('aria-busy', 'true');
        details.forEach(function (input) { input.readOnly = true; });
        update();
    });
    window.addEventListener('pagehide', function () { inputs.forEach(releasePreview); });
    window.addEventListener('pageshow', function (event) {
        if (event.persisted) {
            delete form.dataset.submitting;
            submit.removeAttribute('aria-busy');
            update();
        }
    });
    update();
})();
