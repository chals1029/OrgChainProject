(function () {
    'use strict';

    var MAX_BYTES = 20 * 1024 * 1024;
    var ALLOWED_EXTENSIONS = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'zip', 'png', 'jpg', 'jpeg'];
    var TYPE_LABELS = { in_campus: 'In-Campus', local_off_campus: 'Local Off-Campus' };
    var TYPE_DESCRIPTIONS = {
        in_campus: 'Prepare an in-campus filing under the BatStateU activity checklist.',
        local_off_campus: 'Prepare a local off-campus filing under the CHED and BatStateU requirements.',
    };

    var formatSize = function (bytes) {
        if (bytes >= 1024 * 1024) return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
        if (bytes >= 1024) return Math.max(1, Math.round(bytes / 1024)) + ' KB';
        return bytes + ' B';
    };

    var fileError = function (file) {
        var name = file.name || 'Selected file';
        var dot = name.lastIndexOf('.');
        var extension = dot > -1 ? name.slice(dot + 1).toLowerCase() : '';
        if (ALLOWED_EXTENSIONS.indexOf(extension) === -1) {
            return name + ' is not an accepted file type. Use PDF, Word, Excel, PowerPoint, ZIP, PNG or JPG.';
        }
        if (file.size === 0) return name + ' is empty. Choose a file with content.';
        if (file.size > MAX_BYTES) return name + ' exceeds the 20 MB per-file limit.';
        return '';
    };

    var plural = function (count, singular, pluralForm) {
        return count + ' ' + (count === 1 ? singular : (pluralForm || singular + 's'));
    };

    var listPreview = function (names) {
        if (names.length <= 3) return names.join(', ');
        return names.slice(0, 3).join(', ') + ' and ' + (names.length - 3) + ' more';
    };

    var cleanText = function (text) {
        return (text || '').replace(/\s+/g, ' ').replace(/\*/g, '').trim();
    };

    var fieldLabel = function (el) {
        if (el.dataset.label) return el.dataset.label;
        if (el.getAttribute('aria-label')) return el.getAttribute('aria-label');
        var field = el.closest('.ap-field');
        var candidate = field ? field.querySelector('[data-field-label], .ap-label, legend') : null;
        if (!candidate && el.labels && el.labels.length) {
            var label = el.labels[0];
            candidate = label.contains(el) ? label.querySelector('span') : label;
        }
        var text = candidate ? cleanText(candidate.textContent) : '';
        return text || cleanText((el.name || 'field').replace(/[_\[\]]+/g, ' '));
    };

    var init = function () {
        var form = document.getElementById('activityProposalForm');
        if (!form) return;

        var typeInputs = Array.prototype.slice.call(form.querySelectorAll('input[name="activity_type"]'));
        var requirementPanels = Array.prototype.slice.call(form.querySelectorAll('.ap-requirement-panel[data-requirement-type]'));
        var heading = document.getElementById('requirementsHeading');
        var countEl = document.getElementById('requirementsCount');
        var progress = document.getElementById('requirementsProgress');
        var notice = document.getElementById('activityUploadNotice');
        var remainingCount = document.getElementById('activityRemainingCount');
        var remainingHelp = document.getElementById('activityRemainingHelp');
        var submitButton = document.getElementById('activitySubmitButton');
        var downloadButton = document.getElementById('downloadTemplatesBtn');
        var pageHeaderDesc = document.getElementById('pageHeaderDesc');
        var startsAt = form.querySelector('[name="starts_at"]');
        var endsAt = form.querySelector('[name="ends_at"]');
        var templateUrl = form.dataset.templateUrl || '';
        var activeType = '';

        var rows = [];
        var rowsByInput = new Map();
        requirementPanels.forEach(function (panel) {
            panel.querySelectorAll('[data-requirement-row]').forEach(function (row) {
                var input = row.querySelector('input[type="file"][data-requirement-file]');
                if (!input) return;
                var status = row.querySelector('[data-file-status]');
                var titleEl = row.querySelector('.ap-requirement-title');
                var state = {
                    row: row,
                    panel: panel,
                    type: panel.dataset.requirementType,
                    input: input,
                    icon: row.querySelector('[data-file-icon]'),
                    status: status,
                    uploadLabel: row.querySelector('[data-upload-label]'),
                    required: row.dataset.required === '1',
                    existing: row.dataset.existingFile === '1',
                    currentName: status ? (status.dataset.currentName || '') : '',
                    title: titleEl ? cleanText(titleEl.textContent) : fieldLabel(input),
                    error: '',
                };
                rows.push(state);
                rowsByInput.set(input, state);
            });
        });

        var showNotice = function (message, isError) {
            if (!notice) return;
            notice.textContent = message;
            notice.classList.toggle('is-error', !!isError);
            notice.hidden = false;
        };

        var selectedTypeInput = function () {
            for (var i = 0; i < typeInputs.length; i++) {
                if (typeInputs[i].checked) return typeInputs[i];
            }
            return null;
        };

        var rowIsActive = function (state) {
            return state.type === activeType;
        };

        var rowIsComplete = function (state) {
            if (state.input.files.length > 0) return !state.error;
            return state.existing;
        };

        var renderRow = function (state) {
            var active = rowIsActive(state);
            var file = state.input.files.length ? state.input.files[0] : null;
            var complete = active && rowIsComplete(state);
            var invalid = !!(file && state.error);

            state.input.disabled = !active;
            state.row.classList.toggle('has-file', complete);
            state.row.classList.toggle('is-invalid', invalid);
            if (invalid) state.input.setAttribute('aria-invalid', 'true');
            else state.input.removeAttribute('aria-invalid');

            if (state.icon) {
                state.icon.classList.toggle('bi-check-lg', complete);
                state.icon.classList.toggle('bi-file-earmark-text', !complete);
            }
            if (state.uploadLabel) {
                state.uploadLabel.textContent = file || state.existing ? 'Replace file' : 'Upload file';
            }
            if (state.status) {
                var text;
                if (invalid) text = state.error;
                else if (file) text = file.name + ' (' + formatSize(file.size) + ') — uploads when you submit';
                else if (state.existing) text = state.currentName ? 'Current file: ' + state.currentName : 'Current file on record';
                else text = 'No file selected';
                state.status.textContent = text;
                state.status.classList.toggle('is-error', invalid);
            }
        };

        var validateRowFile = function (state) {
            var file = state.input.files.length ? state.input.files[0] : null;
            state.error = file ? fileError(file) : '';
            state.input.setCustomValidity(state.error);
        };

        var validateSchedule = function () {
            if (!startsAt || !endsAt) return;
            endsAt.min = startsAt.value || '';
            var start = startsAt.valueAsNumber;
            var end = endsAt.valueAsNumber;
            if (isNaN(start)) start = Date.parse(startsAt.value);
            if (isNaN(end)) end = Date.parse(endsAt.value);
            var invalid = endsAt.value !== '' && startsAt.value !== '' && !isNaN(start) && !isNaN(end) && end < start;
            endsAt.setCustomValidity(invalid ? 'The end must be on or after the start date and time.' : '');
        };

        var invalidFields = function () {
            var names = [];
            var elements = form.elements;
            for (var i = 0; i < elements.length; i++) {
                var el = elements[i];
                if (!el.willValidate || el.validity.valid || el.type === 'file') continue;
                var name = fieldLabel(el);
                if (names.indexOf(name) === -1) names.push(name);
            }
            return names;
        };

        var updateReadiness = function () {
            var total = 0;
            var done = 0;
            var missing = [];
            var badFiles = [];
            for (var i = 0; i < rows.length; i++) {
                var state = rows[i];
                if (!rowIsActive(state)) continue;
                if (state.error && state.input.files.length) badFiles.push(state.title);
                if (!state.required) continue;
                total++;
                if (rowIsComplete(state)) done++;
                else missing.push(state.title);
            }
            var fields = invalidFields();
            var remaining = total - done;
            var checklistComplete = remaining === 0;
            var ready = checklistComplete && badFiles.length === 0 && fields.length === 0;

            if (countEl) {
                countEl.textContent = total ? done + ' of ' + total + ' required ready' : 'No required uploads';
                countEl.classList.toggle('is-active', checklistComplete);
                countEl.classList.toggle('is-warning', !checklistComplete);
            }
            if (progress) {
                progress.max = total || 1;
                progress.value = total ? done : 1;
                progress.textContent = total ? done + ' of ' + total : 'Complete';
            }
            if (remainingCount) {
                remainingCount.textContent = remaining
                    ? plural(remaining, 'required document') + ' remaining'
                    : 'All required documents ready';
            }
            if (remainingHelp) {
                if (badFiles.length) {
                    remainingHelp.textContent = 'Replace invalid files before submitting.';
                } else if (fields.length) {
                    remainingHelp.textContent = 'Complete the starred activity fields and check the schedule.';
                } else if (missing.length) {
                    remainingHelp.textContent = 'Upload every required document to submit for review.';
                } else {
                    remainingHelp.textContent = 'Ready to submit for review. Files upload when you submit.';
                }
            }
            if (submitButton) submitButton.disabled = !ready;
            return { ready: ready, missing: missing };
        };

        var applyType = function () {
            var input = selectedTypeInput();
            activeType = input ? input.value : 'in_campus';
            var label = (input && input.dataset.typeLabel) || TYPE_LABELS[activeType] || activeType;

            typeInputs.forEach(function (typeInput) {
                var choice = typeInput.closest('.ap-type-choice');
                if (choice) choice.classList.toggle('is-selected', typeInput.checked);
            });
            if (heading) heading.textContent = label + ' requirements';
            if (pageHeaderDesc && TYPE_DESCRIPTIONS[activeType]) pageHeaderDesc.textContent = TYPE_DESCRIPTIONS[activeType];
            if (downloadButton && templateUrl) {
                try {
                    var url = new URL(templateUrl, window.location.href);
                    url.searchParams.set('type', activeType);
                    downloadButton.href = url.toString();
                } catch (error) {
                    downloadButton.href = templateUrl + (templateUrl.indexOf('?') === -1 ? '?' : '&') + 'type=' + encodeURIComponent(activeType);
                }
            }

            requirementPanels.forEach(function (panel) {
                panel.hidden = panel.dataset.requirementType !== activeType;
            });
            rows.forEach(renderRow);
            updateReadiness();
        };

        var onRequirementFileChange = function (state) {
            validateRowFile(state);
            renderRow(state);
            var file = state.input.files.length ? state.input.files[0] : null;
            if (!file) {
                showNotice(state.existing
                    ? 'Selection cleared for ' + state.title + '; the current file on record stays.'
                    : 'Selection cleared for ' + state.title + '.', false);
            } else if (state.error) {
                showNotice(state.error, true);
            } else {
                showNotice('Selected ' + file.name + ' for ' + state.title + '. It uploads when you submit for review.', false);
            }
        };

        form.addEventListener('change', function (event) {
            var target = event.target;
            if (target.name === 'activity_type') {
                applyType();
                return;
            }
            var state = rowsByInput.get(target);
            if (state) {
                onRequirementFileChange(state);
                updateReadiness();
                return;
            }
            if (target === startsAt || target === endsAt) validateSchedule();
            updateReadiness();
        });

        form.addEventListener('input', function (event) {
            var target = event.target;
            if (target.type === 'file' || target.type === 'checkbox' || target.type === 'radio') return;
            if (target === startsAt || target === endsAt) validateSchedule();
            updateReadiness();
        });

        form.addEventListener('invalid', function (event) {
            var target = event.target;
            if (target.type !== 'file') return;
            showNotice(target.validationMessage, true);
            var state = rowsByInput.get(target);
            if (state) state.row.scrollIntoView({ block: 'center' });
        }, true);

        form.addEventListener('keydown', function (event) {
            if (event.key !== 'Enter') return;
            var target = event.target;
            if (target.tagName === 'INPUT' && target.type !== 'submit' && target.type !== 'button') event.preventDefault();
        });

        form.addEventListener('submit', function (event) {
            if (event.submitter !== undefined && event.submitter !== submitButton) {
                event.preventDefault();
                return;
            }
            applyType();
            for (var i = 0; i < rows.length; i++) {
                var state = rows[i];
                if (state.input.disabled || !state.error) continue;
                event.preventDefault();
                showNotice(state.error, true);
                state.row.scrollIntoView({ block: 'center' });
                return;
            }
            var readiness = updateReadiness();
            if (!readiness.ready) {
                event.preventDefault();
                showNotice(readiness.missing.length
                    ? 'Upload the required checklist items before submitting: ' + listPreview(readiness.missing) + '.'
                    : 'Complete the form before submitting for review.', true);
                return;
            }
            if (submitButton) submitButton.disabled = true;
        });

        window.addEventListener('pageshow', function (event) {
            if (event.persisted) updateReadiness();
        });


        var heightFrame = 0;
        var syncHeight = function () {
            heightFrame = 0;
            var top = form.getBoundingClientRect().top;
            var main = form.closest('.org-main');
            var bottom = main ? Math.min(window.innerHeight, main.getBoundingClientRect().bottom) : window.innerHeight;
            var padding = main ? parseFloat(window.getComputedStyle(main).paddingBottom) || 0 : 0;
            var height = Math.max(320, Math.floor(bottom - top - padding));
            form.style.setProperty('--ap-height', height + 'px');
        };
        var scheduleHeight = function () {
            if (!heightFrame) heightFrame = window.requestAnimationFrame(syncHeight);
        };
        window.addEventListener('resize', scheduleHeight);
        window.addEventListener('load', scheduleHeight);

        rows.forEach(validateRowFile);
        validateSchedule();
        applyType();
        syncHeight();
    };

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();
})();
