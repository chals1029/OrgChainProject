(() => {
    'use strict';

    // Native preview pages have no application shell. Scale each paper independently
    // on screen; named print pages keep their real portrait/landscape dimensions.
    const papers = document.querySelectorAll('[data-ar-paper]');
    if (papers.length) {
        const fit = () => papers.forEach((paper) => {
            const shell = paper.parentElement;
            if (window.matchMedia('print').matches) {
                paper.style.transform = '';
                shell.style.height = '';
                return;
            }
            const scale = Math.min(1, shell.clientWidth / paper.offsetWidth);
            paper.style.transform = `scale(${scale})`;
            shell.style.height = `${Math.ceil(paper.offsetHeight * scale)}px`;
        });
        const observer = new ResizeObserver(fit);
        papers.forEach((paper) => { observer.observe(paper); observer.observe(paper.parentElement); });
        window.addEventListener('beforeprint', () => papers.forEach((paper) => { paper.style.transform = ''; paper.parentElement.style.height = ''; }));
        window.addEventListener('afterprint', fit);
        window.addEventListener('load', fit);
        document.querySelectorAll('[data-ar-print-page]').forEach((button) => button.addEventListener('click', () => window.print()));
        fit();
        return;
    }

    const payload = document.getElementById('orgAccomplishmentData');
    if (!payload) return;
    const data = JSON.parse(payload.textContent);
    const editor = document.getElementById('arEditorDialog');
    const form = document.getElementById('arEditorForm');
    const preview = document.getElementById('arPreviewDialog');
    const frame = document.getElementById('arPreviewFrame');
    const previewStatus = document.getElementById('arPreviewStatus');
    const download = document.getElementById('arPreviewDownload');
    const previewPrint = document.getElementById('arPreviewPrint');
    const evidenceList = document.getElementById('arEvidenceList');
    const evidenceError = document.getElementById('arEvidenceError');
    const objectUrls = new Set();
    let photoIndex = 0;
    let printOnLoad = false;
    let opener = null;

    const make = (tag, className, text) => {
        const node = document.createElement(tag);
        if (className) node.className = className;
        if (text !== undefined) node.textContent = text;
        return node;
    };
    const sdgLabel = (goal) => {
        const text = String(goal).trim();
        const match = text.match(/^(?:SDG\s*)?(\d{1,2})$/i);
        const number = match ? Number(match[1]) : null;
        return number && data.sdgNames[number] ? `SDG ${number}: ${data.sdgNames[number]}` : text;
    };
    const safeUrl = (value) => {
        if (!value) return '';
        try {
            const url = new URL(value, window.location.href);
            return url.origin === window.location.origin && ['http:', 'https:'].includes(url.protocol) ? url.href : '';
        } catch (_) { return ''; }
    };
    const openDialog = (dialog, trigger) => {
        opener = trigger || document.activeElement;
        dialog.showModal();
        document.body.classList.add('ar-dialog-open');
    };
    document.querySelectorAll('[data-ar-close]').forEach((button) => button.addEventListener('click', () => button.closest('dialog').close()));
    document.querySelectorAll('.ar-dialog').forEach((dialog) => {
        dialog.addEventListener('close', () => {
            document.body.classList.remove('ar-dialog-open');
            if (dialog === preview) { frame.src = 'about:blank'; printOnLoad = false; }
            opener?.focus();
        });
    });

    const showPreview = (url, title, exportUrl, trigger, shouldPrint) => {
        const source = safeUrl(url);
        if (!source) return;
        document.getElementById('arPreviewTitle').textContent = title || 'Accomplishment preview';
        const exportSource = safeUrl(exportUrl);
        download.hidden = !exportSource;
        download.href = exportSource;
        previewStatus.hidden = false;
        previewStatus.textContent = 'Loading saved native report…';
        previewPrint.disabled = true;
        printOnLoad = Boolean(shouldPrint);
        const embedded = new URL(source);
        embedded.searchParams.set('embedded', '1');
        frame.src = embedded.href;
        openDialog(preview, trigger);
    };
    document.querySelectorAll('[data-ar-preview]').forEach((button) => button.addEventListener('click', () => showPreview(button.dataset.arPreview, button.dataset.arTitle, button.dataset.arDownload, button, button.hasAttribute('data-ar-print'))));
    // Saved AR documents use the same native preview as the activity builder.
    document.addEventListener('click', (event) => {
        const link = event.target.closest('a[data-acc-preview]');
        if (!link) return;
        event.preventDefault();
        showPreview(link.dataset.accPreview || link.href, link.dataset.accPreviewTitle || 'Semester accomplishment packet', link.dataset.accPreviewDownload, link, false);
    });
    const printPreview = () => {
        try { frame.contentWindow.focus(); frame.contentWindow.print(); }
        catch (_) { previewStatus.hidden = false; previewStatus.textContent = 'Printing could not start. Open the saved report and use your browser’s Print command.'; }
    };
    frame.addEventListener('load', () => {
        if (frame.getAttribute('src') === 'about:blank') return;
        try {
            if (!frame.contentDocument?.querySelector('[data-ar-paper]')) {
                previewStatus.textContent = 'The saved report could not be loaded. Check your access or reopen the report.';
                previewStatus.hidden = false;
                printOnLoad = false;
                return;
            }
            previewStatus.hidden = true;
            previewPrint.disabled = false;
            if (printOnLoad) { printOnLoad = false; printPreview(); }
        } catch (_) { previewStatus.hidden = false; previewStatus.textContent = 'The preview could not be loaded. Please reopen the saved report.'; }
    });
    previewPrint.addEventListener('click', printPreview);
    if (!form || !data.canEdit) return;

    const field = (name) => form.elements.namedItem(name);
    const setField = (name, value) => { const input = field(name); if (input) input.value = value ?? ''; };
    const total = () => {
        const male = field('male_participants').value;
        const female = field('female_participants').value;
        document.getElementById('arParticipantTotal').textContent = male !== '' && female !== '' && /^\d+$/.test(male) && /^\d+$/.test(female) ? (Number(male) + Number(female)).toLocaleString() : '—';
    };
    ['male_participants', 'female_participants'].forEach((name) => field(name).addEventListener('input', total));
    const showContext = (activity) => {
        document.getElementById('arActivityContext').hidden = !activity;
        ['Title', 'Date', 'Time', 'Venue'].forEach((key) => {
            document.getElementById(`arContext${key}`).textContent = activity?.[{Title: 'title', Date: 'date_label', Time: 'time_label', Venue: 'venue'}[key]] || 'Not recorded';
        });
        const goals = document.getElementById('arContextSdgs');
        goals.replaceChildren();
        (activity?.sdg_goals || []).forEach((goal) => goals.append(make('span', 'ar-tag', sdgLabel(goal))));
    };
    const setActivities = (report) => {
        const select = field('org_activity_id');
        select.replaceChildren();
        select.append(new Option(report ? report.title : 'Choose an eligible activity', report ? report.activity_id : ''));
        if (!report) (data.eligible || []).forEach((activity) => select.append(new Option(activity.title, activity.id)));
        select.value = report ? String(report.activity_id) : '';
    };
    field('org_activity_id').addEventListener('change', () => {
        const activity = data.eligible.find((entry) => String(entry.id) === field('org_activity_id').value);
        showContext(activity);
        // Counts and narrative are never copied from the proposal.
        setField('objectives', activity?.objectives || '');
    });
    const clearEvidence = () => {
        objectUrls.forEach((url) => URL.revokeObjectURL(url));
        objectUrls.clear();
        evidenceList.replaceChildren();
        evidenceError.hidden = true;
        photoIndex = 0;
    };
    const evidenceCard = (existing, caption = null) => {
        const card = make('article', 'ar-evidence-card');
        const image = make('img', 'ar-evidence-image');
        image.alt = existing?.caption || 'Selected supporting image preview';
        image.hidden = !existing;
        const link = make('a', 'ar-evidence-name', existing?.name || 'No image selected');
        link.target = '_blank'; link.rel = 'noopener';
        if (existing) {
            image.src = safeUrl(existing.url);
            link.href = safeUrl(existing.url);
            const keep = make('input'); keep.type = 'hidden'; keep.name = 'keep_evidence[]'; keep.value = existing.id; card.append(keep);
        }
        card.append(image, link);
        let localUrl = null;
        if (!existing) {
            const input = make('input', 'ar-photo-input');
            input.type = 'file'; input.name = `photos[${photoIndex}][file]`; input.accept = 'image/jpeg,image/png';
            input.setAttribute('aria-label', 'Choose JPEG or PNG documentation image');
            input.addEventListener('change', () => {
                if (localUrl) { URL.revokeObjectURL(localUrl); objectUrls.delete(localUrl); localUrl = null; }
                const file = input.files[0];
                input.setCustomValidity(file && !['image/jpeg', 'image/png'].includes(file.type) ? 'Choose a JPEG or PNG image.' : file && file.size > 10 * 1024 * 1024 ? 'Each image must be no larger than 10 MB.' : '');
                if (!file || input.validationMessage) {
                    image.hidden = true; link.removeAttribute('href'); link.textContent = file?.name || 'No image selected';
                    if (file) input.reportValidity();
                    return;
                }
                localUrl = URL.createObjectURL(file); objectUrls.add(localUrl);
                image.src = localUrl; image.hidden = false; link.href = localUrl; link.textContent = file.name;
                image.onerror = () => { input.setCustomValidity('This image could not be decoded. Select a valid JPEG or PNG.'); image.hidden = true; input.reportValidity(); };
            });
            card.append(input);
        }
        const label = make('label', 'ar-field'); label.append(make('span', '', 'Image caption *'));
        const text = make('textarea'); text.rows = 3; text.maxLength = 2000; text.required = Boolean(existing);
        text.name = existing ? `evidence_captions[${existing.id}]` : `photos[${photoIndex++}][caption]`;
        text.value = caption ?? existing?.caption ?? '';
        label.append(text); card.append(label);
        const remove = make('button', 'org-btn org-btn-ghost', existing ? 'Remove saved image from report' : 'Remove selected image');
        remove.type = 'button';
        remove.addEventListener('click', () => { if (localUrl) { URL.revokeObjectURL(localUrl); objectUrls.delete(localUrl); } card.remove(); });
        card.append(remove); evidenceList.append(card);
    };
    document.getElementById('arAddPhoto').addEventListener('click', () => {
        if (evidenceList.children.length >= 20) { evidenceError.textContent = 'A report may include up to 20 images. Remove one before adding another.'; evidenceError.hidden = false; return; }
        evidenceCard(null);
    });
    const fields = ['sponsor', 'classification', 'objectives', 'people_involved', 'male_participants', 'female_participants', 'brief_description', 'narrative', 'problems_encountered', 'recommendations'];
    const initializeEditor = (report, old) => {
        form.reset(); clearEvidence();
        form.action = report ? safeUrl(report.update_url) : safeUrl(data.storeUrl);
        setField('_report_id', report?.id || '');
        document.getElementById('arEditorTitle').textContent = report ? 'Edit activity report' : 'Create activity report';
        setActivities(report);
        fields.forEach((name) => setField(name, old ? old[name] : report?.[name]));
        ['secretary', 'auditor', 'president', 'adviser', 'coordinator', 'head'].forEach((role) => setField(`signatories[${role}]`, (old ? old.signatories : report?.signatories)?.[role]));
        if (old && !report) setField('org_activity_id', old.org_activity_id);
        showContext(report || data.eligible.find((entry) => String(entry.id) === field('org_activity_id').value));
        (report?.evidence || []).forEach((entry) => {
            if (!old || (old.keep_evidence || []).includes(entry.id)) evidenceCard(entry, old?.evidence_captions?.[entry.id] ?? entry.caption);
        });
        if (old?.photos) Object.values(old.photos).forEach((photo) => evidenceCard(null, photo.caption || ''));
        if (!evidenceList.children.length) evidenceCard(null);
        const finances = document.getElementById('arFinancialContext');
        finances.hidden = !report;
        finances.textContent = report ? `Saved activity totals: collections ₱${Number(report.financial?.total_collection || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} · expenses ₱${Number(report.financial?.total_expenses || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}. The ledger snapshot will refresh on Save.` : '';
        const errors = document.getElementById('arEditorErrors');
        if (errors) errors.hidden = !old;
        total();
    };
    document.querySelectorAll('[data-ar-create]').forEach((button) => button.addEventListener('click', () => { initializeEditor(null, null); openDialog(editor, button); }));
    document.querySelectorAll('[data-ar-edit]').forEach((button) => button.addEventListener('click', () => {
        const report = data.reports.find((entry) => String(entry.id) === button.dataset.arEdit);
        if (!report) return;
        initializeEditor(report, null); openDialog(editor, button);
    }));
    form.addEventListener('submit', (event) => {
        if (form.dataset.submitting) { event.preventDefault(); return; }
        const cards = Array.from(evidenceList.children);
        const present = cards.filter((card) => card.querySelector('input[name="keep_evidence[]"]') || card.querySelector('input[type="file"]')?.files.length);
        if (!present.length || present.length > 20) {
            event.preventDefault(); evidenceError.textContent = 'Keep or select at least one captioned image (maximum 20).'; evidenceError.hidden = false; evidenceError.scrollIntoView({ block: 'center' }); return;
        }
        // Empty picker rows are not evidence and must not create multipart rows.
        cards.filter((card) => !present.includes(card)).forEach((card) => card.remove());
        if (!form.checkValidity()) { event.preventDefault(); form.reportValidity(); return; }
        form.dataset.submitting = 'true'; form.setAttribute('aria-busy', 'true');
        const save = document.getElementById('arSave'); save.disabled = true; save.textContent = 'Saving report & generating Word…';
    });
    // Native submit validation runs before submit handlers. Empty optional picker
    // captions must not block a retained-image edit; selected images still require captions.
    form.addEventListener('input', () => evidenceList.querySelectorAll('.ar-evidence-card').forEach((card) => {
        const input = card.querySelector('input[type="file"]');
        card.querySelector('textarea').required = !input || Boolean(input.files.length);
    }));
    evidenceList.addEventListener('change', () => evidenceList.querySelectorAll('.ar-evidence-card').forEach((card) => {
        const input = card.querySelector('input[type="file"]');
        card.querySelector('textarea').required = !input || Boolean(input.files.length);
    }));
    if (editor.hasAttribute('data-open-on-load') && data.old) {
        const report = data.reports.find((entry) => String(entry.id) === String(data.old._report_id));
        initializeEditor(report || null, data.old);
        openDialog(editor);
        document.getElementById('arEditorErrors')?.focus();
    }
    window.addEventListener('pagehide', () => objectUrls.forEach((url) => URL.revokeObjectURL(url)));
})();
