(() => {
    'use strict';

    const categories = [
        { key: 'academic_awards', title: 'Academic awards', fields: ['level', 'place'] },
        { key: 'leadership_seminars', title: 'Leadership seminars', fields: ['level', 'days'] },
        { key: 'leadership_positions', title: 'Leadership positions', fields: ['level', 'role'] },
        { key: 'leadership_awards', title: 'Leadership awards', fields: ['level'] },
        { key: 'social_activities', title: 'Social responsibility activities', fields: ['scope', 'level', 'role'] },
    ];
    const breakdownRows = [
        ['Grades', 'grades_milli', 'grades_milli', 30],
        ['Academic awards', 'academic_awards_raw_milli', 'academic_awards_milli', 10],
        ['Academic total', 'academic_milli', 'academic_milli', 40],
        ['Leadership · international', 'leadership_international_raw_milli', 'leadership_international_milli', 6],
        ['Leadership · national', 'leadership_national_raw_milli', 'leadership_national_milli', 12],
        ['Leadership · local', 'leadership_local_raw_milli', 'leadership_local_milli', 12],
        ['Leadership total', 'leadership_milli', 'leadership_milli', 30],
        ['Social · non-school', 'social_non_school_raw_milli', 'social_non_school_milli', 18],
        ['Social · school', 'social_school_raw_milli', 'social_school_milli', 12],
        ['Social total', 'social_milli', 'social_milli', 30],
    ];
    let config;
    let ranking = [];
    let current = null;
    let generation = 0;
    let rowId = 0;
    const pending = new Set();
    const el = id => document.getElementById(id);
    const escape = value => config.escapeHtml(value);
    const points = value => Number.isFinite(Number(value)) && value !== null && value !== undefined
        ? (Number(value) / 1000).toFixed(3).replace(/\.?0+$/, '') : 'Not evaluated';
    const human = value => String(value || '').replaceAll('_', ' ');

    function renderRanking() {
        if (!config) return;
        const select = el('tosaRankingYear');
        const selected = select.value;
        const years = [...new Set([
            ...config.applicants.map(applicant => applicant.academicYear),
            ...ranking.map(row => row.academic_year),
        ].filter(Boolean))].sort().reverse();
        select.innerHTML = years.length ? years.map(year => `<option value="${escape(year)}">${escape(year)}</option>`).join('')
            : '<option value="">No academic years available</option>';
        if (years.includes(selected)) select.value = selected;
        select.disabled = !years.length;
        // Preserve the server's competition ranks and cutoff ties, without client sorting or slicing.
        const rows = ranking.filter(row => row.academic_year === select.value);
        el('tosaTop10TableTbody').innerHTML = rows.length ? rows.map(row => {
            const applicant = config.applicants.find(item => Number(item.id) === Number(row.applicant_id));
            return `<tr><td>${escape(row.rank)}</td>
                <td>${escape(applicant?.name || 'Applicant unavailable')}<span class="tosa-secondary">${escape(applicant?.studentId || '')}</span></td>
                <td>${escape(points(row.total_milli))}</td>
                <td>${row.cutoff_tie ? 'Cutoff tie · committee decision pending' : row.tied ? 'Shared rank' : 'No tie'}</td>
                <td>${applicant ? config.dossierButton(applicant) : ''}</td></tr>`;
        }).join('') : '<tr><td colspan="5" class="tosa-empty">No finalized, verified eligible accepted applicants are ranked for this academic year.</td></tr>';
    }

    function options(category, field) {
        if (field === 'level') return category.key === 'social_activities'
            ? ['international', 'national', 'provincial', 'local'] : ['international', 'national', 'local'];
        if (field === 'scope') return ['non_school', 'school'];
        return category.key === 'leadership_positions' ? ['president', 'vice_president', 'other']
            : ['organizer', 'team_leader', 'participant', 'performer'];
    }

    function addRow(category, value = {}) {
        const container = el(`tosaRows_${category.key}`);
        const row = document.createElement('div');
        row.className = 'tosa-evidence-row';
        const prefix = `tosaClaim_${++rowId}`;
        const fields = ['label', 'evidence', ...category.fields];
        row.innerHTML = fields.map(field => {
            const id = `${prefix}_${field}`;
            const title = field === 'label' ? 'Actual achievement / title' : field === 'evidence'
                ? 'Evidence · section + page / reference' : field === 'days' ? 'Verified attended days (1–5)' : human(field);
            let control;
            if (field === 'label' || field === 'evidence') {
                control = `<input id="${id}" class="tosa-input" data-field="${field}" maxlength="200" required>`;
            } else if (field === 'place' || field === 'days') {
                control = `<input id="${id}" class="tosa-input" data-field="${field}" type="number" min="1" max="${field === 'place' ? 10 : 5}" step="1" required>`;
            } else {
                control = `<select id="${id}" class="tosa-input" data-field="${field}" required><option value="">Choose ${field}</option>${options(category, field).map(option => `<option value="${option}">${human(option)}</option>`).join('')}</select>`;
            }
            return `<label class="tosa-field" for="${id}">${title}${control}</label>`;
        }).join('') + (config.canReview ? '<button type="button" class="org-btn org-btn-ghost org-btn-sm" data-remove-claim>Remove claim</button>' : '');
        fields.forEach(field => { row.querySelector(`[data-field="${field}"]`).value = value[field] ?? ''; });
        row.querySelector('[data-remove-claim]')?.addEventListener('click', () => {
            row.remove();
            updateCount(category);
        });
        container.append(row);
        updateCount(category);
        return row;
    }

    function updateCount(category) {
        const count = el(`tosaRows_${category.key}`).children.length;
        el(`tosaCount_${category.key}`).textContent = count ? `${count} claim${count === 1 ? '' : 's'}` : '0 verified claims';
        const add = el(`tosaAdd_${category.key}`);
        if (add) add.disabled = count >= 200;
    }

    function renderSummary(evaluation) {
        const summary = el('tosaScoreSummary');
        el('tosaScoreWorkbookLinks').hidden = !evaluation;
        if (!evaluation) {
            summary.textContent = 'Awaiting evaluator-verified scoring. No score or official rank exists yet.';
            return;
        }
        const input = evaluation.input || {};
        const b = evaluation.breakdown || {};
        const entries = evaluation.entries || [];
        const finalized = evaluation.status === 'finalized';
        summary.innerHTML = `<p><strong>${finalized ? 'Finalized verified evaluation' : 'Provisional evaluation · not eligible for ranking'} · ${escape(points(b.total_milli))} / 100</strong></p>
            <p class="tosa-note">GWA: ${escape(input.gwa ?? 'Not provided')} · ${input.gwa_verified ? 'GWA verified' : 'GWA not verified'} · ${input.eligible_verified ? 'Eligibility verified' : 'Eligibility not verified'}.</p>
            ${evaluation.invalidated_reason ? `<p class="tosa-error">Verification invalidated: ${escape(evaluation.invalidated_reason)}</p>` : ''}
            <p class="tosa-note">Rubric: ${escape(evaluation.rubric_version)}. ${evaluation.verified_at ? `Verified ${escape(evaluation.verified_at)}.` : 'Not finalized.'} Unsaved edits below do not change this server-calculated score.</p>
            <details><summary>Server calculation and evidence (${entries.length} entries)</summary>
                <div class="tosa-table-wrap"><table class="tosa-table"><thead><tr><th>Component</th><th>Before cap</th><th>Applied</th><th>Cap</th></tr></thead><tbody>
                ${breakdownRows.map(([label, raw, applied, cap]) => `<tr><th scope="row">${label}</th><td>${escape(points(b[raw]))}</td><td>${escape(points(b[applied]))}</td><td>${cap}</td></tr>`).join('')}
                </tbody></table></div>
                <div class="tosa-table-wrap"><table class="tosa-table"><thead><tr><th>Actual entry</th><th>Calculation inputs</th><th>Evidence</th><th>Uncapped points</th></tr></thead><tbody>
                ${entries.length ? entries.map(entry => `<tr><td>${escape(entry.label)}<span class="tosa-secondary">${escape(human(entry.category))}</span></td><td>${escape([entry.level, entry.scope, entry.role].filter(Boolean).map(human).join(' · '))}${entry.place !== undefined ? ` · place ${escape(entry.place)}` : ''}${entry.days !== undefined ? ` · ${escape(entry.days)} attended day(s)` : ''}</td><td>${escape(entry.evidence)}</td><td>${escape(points(entry.points_milli))}</td></tr>`).join('') : '<tr><td colspan="4">No verified claims were entered.</td></tr>'}
                </tbody></table></div>
            </details>`;
    }

    function clearErrors() {
        el('tosaScoringFeedback').textContent = '';
        el('tosaScoringFeedback').classList.remove('tosa-error');
        el('tosaScoringForm').querySelectorAll('[aria-invalid]').forEach(input => input.removeAttribute('aria-invalid'));
    }

    function renderForm(applicant) {
        clearErrors();
        const input = applicant.evaluation?.input || {};
        el('tosaScoreYear').value = applicant.academicYear || '';
        el('tosaScoreGwa').value = input.gwa ?? applicant.gwa ?? '';
        el('tosaScoreGwaVerified').checked = input.gwa_verified ?? applicant.gwaVerified ?? false;
        el('tosaScoreEligible').checked = input.eligible_verified === true;
        categories.forEach(category => {
            el(`tosaRows_${category.key}`).replaceChildren();
            (input[category.key] || []).forEach(row => addRow(category, row));
            updateCount(category);
            el(`tosaCategory_${category.key}`).open = false;
        });
        renderSummary(applicant.evaluation);
        el('tosaScoreWorkbookPreview').href = applicant.workbookUrl;
        el('tosaScoreWorkbookDownload').href = applicant.workbookDownloadUrl;
        syncLocks();
    }

    function syncLocks() {
        const busy = pending.size > 0;
        el('tosaScoringBusy').hidden = !busy;
        el('tosaScoringFields').disabled = !config.canReview || !current || busy;
        el('tosaReviewForm').querySelectorAll('input:not([name="_token"]), select, textarea, button').forEach(control => {
            control.disabled = !config.canReview || !current || busy;
        });
    }

    function validationErrors(errors) {
        let first;
        const common = { academic_year: 'tosaScoreYear', gwa: 'tosaScoreGwa', gwa_verified: 'tosaScoreGwaVerified', eligible_verified: 'tosaScoreEligible' };
        Object.keys(errors || {}).forEach(key => {
            const [category, index, field] = key.split('.');
            let control = common[key] ? el(common[key]) : null;
            if (categories.some(item => item.key === category)) {
                const details = el(`tosaCategory_${category}`);
                details.open = true;
                if (index !== undefined && field) control = el(`tosaRows_${category}`).children[Number(index)]?.querySelector(`[data-field="${field}"]`);
            }
            if (control) {
                control.setAttribute('aria-invalid', 'true');
                first ||= control;
            }
        });
        first?.focus();
        return Object.values(errors || {}).flat().join(' ');
    }

    async function save(event) {
        event.preventDefault();
        if (!config.canReview || !current || pending.size > 0) return;
        const form = el('tosaScoringForm');
        if (!form.reportValidity()) return;
        const applicant = current;
        const requestGeneration = generation;
        const payload = {
            intent: event.submitter?.value === 'finalize' ? 'finalize' : 'draft',
            academic_year: el('tosaScoreYear').value.trim(),
            gwa: el('tosaScoreGwa').value === '' ? null : Number(el('tosaScoreGwa').value),
            gwa_verified: el('tosaScoreGwaVerified').checked,
            eligible_verified: el('tosaScoreEligible').checked,
        };
        categories.forEach(category => {
            payload[category.key] = [...el(`tosaRows_${category.key}`).children].map(row => {
                const claim = {};
                row.querySelectorAll('[data-field]').forEach(control => {
                    const field = control.dataset.field;
                    claim[field] = field === 'place' || field === 'days' ? Number(control.value) : control.value.trim();
                });
                return claim;
            });
        });
        clearErrors();
        pending.add(applicant.id);
        syncLocks();
        el('tosaScoringFeedback').textContent = 'Saving server-calculated evaluation…';
        try {
            const response = await fetch(applicant.scoreUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': form.querySelector('[name="_token"]').value },
                body: JSON.stringify(payload),
            });
            const result = await response.json();
            pending.delete(applicant.id);
            syncLocks();
            if (!response.ok || !result.ok) {
                const messages = current === applicant && generation === requestGeneration
                    ? validationErrors(result.errors) : Object.values(result.errors || {}).flat().join(' ');
                throw new Error(messages || result.message || 'The evaluation could not be saved.');
            }
            // Never derive totals, rank, verification or finalization from the submitted inputs.
            Object.assign(applicant, { evaluation: result.evaluation, gwa: result.gwa, gwaVerified: result.gwaVerified, academicYear: result.academicYear });
            ranking = result.ranking;
            renderRanking();
            if (current === applicant) {
                renderForm(applicant);
                config.onSaved(applicant);
                if (generation === requestGeneration) el('tosaScoringFeedback').textContent = result.evaluation.status === 'finalized'
                    ? 'Verified evaluation finalized. Qualification stage remains unchanged.' : 'Provisional evaluation saved. It does not contribute to ranking.';
            }
        } catch (error) {
            if (current === applicant && generation === requestGeneration) {
                el('tosaScoringFeedback').classList.add('tosa-error');
                el('tosaScoringFeedback').textContent = error instanceof SyntaxError || error instanceof TypeError
                    ? 'The evaluation could not be saved. Check your connection or session and try again.' : error.message;
            }
        } finally {
            pending.delete(applicant.id);
            syncLocks();
        }
    }

    window.TosaScoring = {
        init(options) {
            config = options;
            ranking = options.ranking;
            const container = el('tosaEvidenceCategories');
            categories.forEach(category => {
                const details = document.createElement('details');
                details.id = `tosaCategory_${category.key}`;
                details.className = 'tosa-evidence-category';
                details.innerHTML = `<summary>${category.title} · <span id="tosaCount_${category.key}">0 verified claims</span></summary><div id="tosaRows_${category.key}"></div>`
                    + (config.canReview ? `<button type="button" id="tosaAdd_${category.key}" class="org-btn org-btn-ghost org-btn-sm">Add verified claim</button>` : '');
                container.append(details);
                el(`tosaAdd_${category.key}`)?.addEventListener('click', () => {
                    if (!current || pending.size > 0 || el(`tosaRows_${category.key}`).children.length >= 200) return;
                    const row = addRow(category);
                    row.querySelector('input').focus();
                });
            });
            el('tosaRankingYear').addEventListener('change', renderRanking);
            el('tosaScoringForm').addEventListener('submit', save);
            el('tosaScoringForm').addEventListener('invalid', event => {
                const details = event.target.closest('details');
                if (details) details.open = true;
            }, true);
            el('applicantReviewModal').addEventListener('close', () => {
                if (!el('applicantReviewModal').open) this.close();
            });
        },
        open(applicant) { current = applicant; generation++; renderForm(applicant); },
        close() { current = null; generation++; if (config) syncLocks(); },
        session() { return generation; },
        beginReview(applicant) {
            if (pending.size > 0) return false;
            pending.add(applicant.id);
            syncLocks();
            return true;
        },
        endReview(applicant) { pending.delete(applicant.id); syncLocks(); },
        applyReview(applicant, result) {
            ranking = result.ranking;
            renderRanking();
            if (current === applicant) {
                renderForm(applicant);
                config.onSaved(applicant);
            }
        },
        renderRanking,
    };
})();
