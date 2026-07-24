<?php
    $voters = $voters ?? [];
    $collegeOptions = $collegeOptions ?? [];
    $filterSrCode = trim((string) ($filterSrCode ?? ''));
    $filterCollege = trim((string) ($filterCollege ?? ''));
    $voterCount = (int) ($voterCount ?? count($voters));
    $hasFilters = $filterSrCode !== '' || $filterCollege !== '';
    $collegeSelectOptions = $collegeOptions !== [] ? $collegeOptions : [
        'College of Accountancy, Business, Economics, and International Hospitality Management',
        'College of Arts and Sciences',
        'College of Criminal Justice Education',
        'College of Nursing and Allied Health Sciences',
        'College of Informatics and Computing Sciences',
        'College of Teacher Education',
        'Laboratory School',
    ];
    $yearLevelOptions = [
        'First' => 'First Year',
        'Second' => 'Second Year',
        'Third' => 'Third Year',
        'Fourth' => 'Fourth Year',
    ];
    $gradeLevelOptions = [
        'Eleven' => 'Grade Eleven',
        'Twelve' => 'Grade Twelve',
    ];
?>

<div class="admin-grid voter-admin-grid">
    <section class="admin-panel voter-registry-panel">
        <div class="panel-heading voter-registry-heading">
            <div>
                <p class="eyebrow">Registry</p>
                <h2>Official Voter List</h2>
            </div>
            <div class="panel-actions">
                <form method="post" action="<?= e(voting_url('/admin/voters/reset-all-votes')) ?>" onsubmit="return confirm('Reset ALL votes? Every voter will be marked as not yet voted and all ballot records will be removed.');">
                    <?= voting_csrf_field() ?>
                    <button class="btn btn-outline-brown btn-sm" type="submit">
                        <i class="bi bi-arrow-counterclockwise"></i>
                        <span>Reset All Votes</span>
                    </button>
                </form>
                <form method="post" action="<?= e(voting_url('/admin/voters/delete-all')) ?>" onsubmit="return confirm('Delete ALL voters? This also removes linked votes and receipts and cannot be undone.');">
                    <?= voting_csrf_field() ?>
                    <button class="btn btn-outline-danger btn-sm" type="submit">
                        <i class="bi bi-trash3"></i>
                        <span>Delete All Voters</span>
                    </button>
                </form>
                <button class="btn btn-outline-brown btn-sm" type="button" onclick="openImportModal()">
                    <i class="bi bi-file-earmark-spreadsheet"></i>
                    <span>Bulk Import</span>
                </button>
                <button class="btn btn-brown btn-sm voter-add-btn" type="button" onclick="openVoterModal()">
                    <i class="bi bi-person-plus-fill"></i>
                    <span>Add Voter</span>
                </button>
                <span class="status-pill is-pending"><?= e($voterCount) ?> <?= $hasFilters ? 'Matched' : 'Total' ?></span>
            </div>
        </div>

        <form action="<?= e(voting_url('/admin/voters')) ?>" method="GET" class="voter-filter-bar">
            <label class="voter-filter-field" for="voterSrCodeFilter">
                <span><i class="bi bi-search"></i> Search SR Code</span>
                <input
                    id="voterSrCodeFilter"
                    name="sr_code"
                    value="<?= e($filterSrCode) ?>"
                    placeholder="Example: 22-74239"
                    autocomplete="off">
            </label>

            <label class="voter-filter-field" for="voterCollegeFilter">
                <span><i class="bi bi-building"></i> College</span>
                <select id="voterCollegeFilter" name="college">
                    <option value="">All Colleges</option>
                    <?php foreach ($collegeOptions as $collegeName): ?>
                        <option value="<?= e($collegeName) ?>" <?= $filterCollege === $collegeName ? 'selected' : '' ?>>
                            <?= e(college_abbreviation($collegeName)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <div class="voter-filter-actions">
                <button class="btn btn-brown voter-filter-submit" type="submit">
                    <i class="bi bi-funnel"></i> Filter
                </button>

                <?php if ($hasFilters): ?>
                    <a href="<?= e(voting_url('/admin/voters')) ?>" class="btn-filter-reset voter-filter-reset">
                        <i class="bi bi-x-circle"></i> Clear
                    </a>
                <?php endif; ?>
            </div>
        </form>

        <div class="voter-table-summary">
            <span><?= e(count($voters)) ?> shown</span>
            <?php if ($voterCount > count($voters)): ?>
                <span>Showing first <?= e(count($voters)) ?> of <?= e($voterCount) ?> matching records</span>
            <?php endif; ?>
        </div>

        <div class="table-responsive voter-table-wrap">
            <table class="table align-middle voter-table">
                <colgroup>
                    <col class="voter-col-sr">
                    <col class="voter-col-person">
                    <col class="voter-col-academic">
                    <col class="voter-col-status">
                    <col class="voter-col-actions">
                </colgroup>
                <thead>
                    <tr>
                        <th>SR Code</th>
                        <th>Voter</th>
                        <th>Academic Info</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($voters)): ?>
                        <tr>
                            <td colspan="5">
                                <div class="voter-empty-state">
                                    <i class="bi bi-search"></i>
                                    <strong>No voters found</strong>
                                    <span>Try another SR code or choose a different college.</span>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($voters as $voter): ?>
                        <?php
                            $yearLevel = (string) ($voter['year_level'] ?? '');
                            $gradeLevel = (string) ($voter['grade_level'] ?? '');
                            $level = $yearLevel !== '' ? $yearLevel : $gradeLevel;
                            $levelLabel = $yearLevel !== ''
                                ? ($yearLevelOptions[$yearLevel] ?? $yearLevel)
                                : ($gradeLevelOptions[$gradeLevel] ?? $gradeLevel);
                            $hasVoted = (int) ($voter['has_voted'] ?? 0) === 1;
                            $initials = strtoupper(substr($voter['full_name'], 0, 1));
                        ?>
                        <tr class="voter-row-new">
                            <td>
                                <span class="voter-sr-pill"><?= e($voter['sr_code']) ?></span>
                            </td>
                            <td>
                                <div class="voter-profile-box">
                                    <div class="voter-id-info">
                                        <span class="voter-name-label"><?= e($voter['full_name']) ?></span>
                                        <span class="voter-email-sub"><?= e($voter['email']) ?></span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="voter-academic-stack">
                                    <div class="college-tag"><?= e($voter['college']) ?></div>
                                    <div class="program-text"><?= e($voter['program'] ?: 'No program listed') ?></div>
                                    <div class="level-badge"><?= $level !== '' ? e($levelLabel) : 'Level not set' ?></div>
                                </div>
                            </td>
                            <td>
                                <div class="status-alignment">
                                    <span class="voter-status-badge <?= $hasVoted ? 'is-voted' : 'is-pending' ?>">
                                        <i class="bi <?= $hasVoted ? 'bi-check-circle-fill' : 'bi-clock-history' ?>"></i>
                                        <?= $hasVoted ? 'Voted' : 'Not yet' ?>
                                    </span>
                                </div>
                            </td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-2 flex-wrap">
                                    <button
                                        class="btn btn-outline-brown btn-sm"
                                        type="button"
                                        onclick="openEditVoterModal(this)"
                                        data-voter-id="<?= e((string) ($voter['id'] ?? 0)) ?>"
                                        data-sr-code="<?= e((string) ($voter['sr_code'] ?? '')) ?>"
                                        data-email="<?= e((string) ($voter['email'] ?? '')) ?>"
                                        data-full-name="<?= e((string) ($voter['full_name'] ?? '')) ?>"
                                        data-college="<?= e((string) ($voter['college'] ?? '')) ?>"
                                        data-program="<?= e((string) ($voter['program'] ?? '')) ?>"
                                        data-year-level="<?= e((string) ($voter['year_level'] ?? '')) ?>"
                                        data-grade-level="<?= e((string) ($voter['grade_level'] ?? '')) ?>">
                                        <i class="bi bi-pencil-square"></i> Edit
                                    </button>
                                    <form method="post" action="<?= e(voting_url('/admin/voters/reset-vote')) ?>" onsubmit="return confirm('Reset this voter vote? They will be able to submit a ballot again.');">
                                        <?= voting_csrf_field() ?>
                                        <input type="hidden" name="voter_id" value="<?= e((string) ($voter['id'] ?? 0)) ?>">
                                        <button class="btn btn-outline-brown btn-sm" type="submit" <?= $hasVoted ? '' : 'disabled' ?>>
                                            <i class="bi bi-arrow-counterclockwise"></i> Reset Vote
                                        </button>
                                    </form>
                                    <form method="post" action="<?= e(voting_url('/admin/voters/delete')) ?>" onsubmit="return confirm('Delete this voter? Any linked ballot and receipt will also be removed.');">
                                        <?= voting_csrf_field() ?>
                                        <input type="hidden" name="voter_id" value="<?= e((string) ($voter['id'] ?? 0)) ?>">
                                        <button class="btn btn-outline-danger btn-sm" type="submit">
                                            <i class="bi bi-trash3"></i> Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <div class="admin-actions-column voter-actions-column">
        <!-- Modal 1: Add Individual Voter -->
        <div class="voter-modal-overlay" id="voterModalOverlay" onclick="closeVoterModal(event)">
            <section class="admin-panel voter-modal-content" id="voterModalContent">
                <div class="panel-heading">
                    <div>
                        <p class="eyebrow">Registry Management</p>
                        <h2>Add Individual Voter</h2>
                    </div>
                    <button type="button" class="btn-close-modal" onclick="closeVoterModal(event, true)">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <form method="post" action="<?= e(voting_url('/admin/voters/add')) ?>" class="mt-3">
                    <?= voting_csrf_field() ?>
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="sr_code">SR Code</label>
                            <input class="form-control" id="sr_code" name="sr_code" value="<?= e(voting_old('sr_code')) ?>" required placeholder="21-XXXXX">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="email">Email</label>
                            <input class="form-control" id="email" type="email" name="email" value="<?= e(voting_old('email')) ?>" required placeholder="student@g.batstate-u.edu.ph">
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="full_name">Full Name</label>
                            <input class="form-control" id="full_name" name="full_name" value="<?= e(voting_old('full_name')) ?>" required placeholder="Juan Dela Cruz">
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="voterCollege">College / Department</label>
                            <select class="form-select" id="voterCollege" name="college" required>
                                <option value="">Select College</option>
                                <?php foreach ($collegeSelectOptions as $collegeName): ?>
                                    <option value="<?= e($collegeName) ?>" <?= voting_old('college') === $collegeName ? 'selected' : '' ?>>
                                        <?= e(college_abbreviation($collegeName)) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="program">Program</label>
                            <input class="form-control" id="program" name="program" value="<?= e(voting_old('program')) ?>" placeholder="BS Information Technology">
                        </div>
                        <div class="col-6">
                            <label class="form-label" for="year_level">Year</label>
                            <select class="form-select" id="year_level" name="year_level">
                                <option value="">Select Year</option>
                                <?php foreach ($yearLevelOptions as $yearValue => $yearLabel): ?>
                                    <option value="<?= e($yearValue) ?>" <?= voting_old('year_level') === $yearValue ? 'selected' : '' ?>>
                                        <?= e($yearLabel) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label" for="grade_level">Grade</label>
                            <select class="form-select" id="grade_level" name="grade_level">
                                <option value="">Select Grade</option>
                                <?php foreach ($gradeLevelOptions as $gradeValue => $gradeLabel): ?>
                                    <option value="<?= e($gradeValue) ?>" <?= voting_old('grade_level') === $gradeValue ? 'selected' : '' ?>>
                                        <?= e($gradeLabel) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer-actions mt-4">
                        <button class="btn btn-outline-brown" type="button" onclick="closeVoterModal(event, true)">Cancel</button>
                        <button class="btn btn-brown px-5" type="submit" id="saveVoterBtn" disabled>
                            <i class="bi bi-save"></i> Save Voter
                        </button>
                    </div>
                </form>
            </section>
        </div>

        <!-- Modal: Edit Voter -->
        <div class="voter-modal-overlay" id="editVoterModalOverlay" onclick="closeEditVoterModal(event)">
            <section class="admin-panel voter-modal-content" id="editVoterModalContent" onclick="event.stopPropagation();">
                <div class="panel-heading">
                    <div>
                        <p class="eyebrow">Registry Management</p>
                        <h2>Edit Student Information</h2>
                    </div>
                    <button type="button" class="btn-close-modal" onclick="closeEditVoterModal(event, true)">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <form method="post" action="<?= e(voting_url('/admin/voters/update')) ?>" class="mt-3">
                    <?= voting_csrf_field() ?>
                    <input type="hidden" name="voter_id" id="edit_voter_id" value="">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="edit_sr_code">SR Code</label>
                            <input class="form-control" id="edit_sr_code" name="sr_code" required placeholder="21-XXXXX">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="edit_email">Email</label>
                            <input class="form-control" id="edit_email" type="email" name="email" required placeholder="student@g.batstate-u.edu.ph">
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="edit_full_name">Full Name</label>
                            <input class="form-control" id="edit_full_name" name="full_name" required placeholder="Juan Dela Cruz">
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="edit_voter_college">College / Department</label>
                            <select class="form-select" id="edit_voter_college" name="college" required>
                                <option value="">Select College</option>
                                <?php foreach ($collegeSelectOptions as $collegeName): ?>
                                    <option value="<?= e($collegeName) ?>"><?= e(college_abbreviation($collegeName)) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="edit_program">Program</label>
                            <input class="form-control" id="edit_program" name="program" placeholder="BS Information Technology">
                        </div>
                        <div class="col-6">
                            <label class="form-label" for="edit_year_level">Year</label>
                            <select class="form-select" id="edit_year_level" name="year_level">
                                <option value="">Select Year</option>
                                <?php foreach ($yearLevelOptions as $yearValue => $yearLabel): ?>
                                    <option value="<?= e($yearValue) ?>"><?= e($yearLabel) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label" for="edit_grade_level">Grade</label>
                            <select class="form-select" id="edit_grade_level" name="grade_level">
                                <option value="">Select Grade</option>
                                <?php foreach ($gradeLevelOptions as $gradeValue => $gradeLabel): ?>
                                    <option value="<?= e($gradeValue) ?>"><?= e($gradeLabel) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer-actions mt-4">
                        <button class="btn btn-outline-brown" type="button" onclick="closeEditVoterModal(event, true)">Cancel</button>
                        <button class="btn btn-brown px-5" type="submit">
                            <i class="bi bi-save"></i> Save Changes
                        </button>
                    </div>
                </form>
            </section>
        </div>

        <!-- Modal 2: CSV Bulk Import -->
        <div class="voter-modal-overlay" id="voterImportModalOverlay" onclick="closeImportModal(event)">
            <section class="admin-panel voter-modal-content" id="voterImportModalContent">
                <div class="panel-heading">
                    <div>
                        <p class="eyebrow">Bulk Actions</p>
                        <h2>CSV Import</h2>
                    </div>
                    <button type="button" class="btn-close-modal" onclick="closeImportModal(event, true)">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <form id="importForm" method="post" action="<?= e(voting_url('/admin/voters/import')) ?>" enctype="multipart/form-data" class="mt-3">
                    <?= voting_csrf_field() ?>
                    <div class="csv-format-note mb-4">
                        <i class="bi bi-info-circle-fill"></i>
                        <span><strong>Official Format:</strong> SR-Code, Email Address, Full Name, Department, Program, Year Level.</span>
                    </div>
                    
                    <div class="import-upload-zone" id="dropZone">
                        <i class="bi bi-cloud-arrow-up"></i>
                        <p>Click to browse or drag and drop your CSV file here</p>
                        <input type="file" name="voter_csv" id="voterCsv" accept=".csv,text/csv" required>
                        <div id="fileNameDisplay" class="mt-2 fw-bold text-success" style="display: none;"></div>
                    </div>

                    <div class="modal-footer-actions mt-4">
                        <button class="btn btn-outline-brown" type="button" onclick="closeImportModal(event, true)">Cancel</button>
                        <button class="btn btn-brown px-5" type="submit" id="importBtn" disabled>
                            <i class="bi bi-upload"></i> Upload & Sync Registry
                        </button>
                    </div>
                </form>
            </section>
        </div>
    </div>
</div>

<script>
    function openVoterModal() {
        const overlay = document.getElementById('voterModalOverlay');
        const content = document.getElementById('voterModalContent');
        if (!overlay || !content) return;

        overlay.style.display = 'flex';
        setTimeout(() => {
            overlay.classList.add('is-open');
            content.classList.add('is-open');
        }, 10);
        document.body.style.overflow = 'hidden';
    }

    function closeVoterModal(event, force = false) {
        const overlay = document.getElementById('voterModalOverlay');
        const content = document.getElementById('voterModalContent');
        if (!overlay || !content) return;

        if (force || event.target === overlay) {
            overlay.classList.remove('is-open');
            content.classList.remove('is-open');
            setTimeout(() => {
                overlay.style.display = 'none';
            }, 300);
            document.body.style.overflow = '';
        }
    }

    function normalizedYearLevel(value) {
        const normalized = String(value || '').trim().toLowerCase().replace(/[^a-z0-9]+/g, ' ').replace(/\s+/g, ' ').trim();
        const map = {
            '1': 'First',
            '1st': 'First',
            '1st year': 'First',
            'first': 'First',
            'first year': 'First',
            '2': 'Second',
            '2nd': 'Second',
            '2nd year': 'Second',
            'second': 'Second',
            'second year': 'Second',
            '3': 'Third',
            '3rd': 'Third',
            '3rd year': 'Third',
            'third': 'Third',
            'third year': 'Third',
            '4': 'Fourth',
            '4th': 'Fourth',
            '4th year': 'Fourth',
            'fourth': 'Fourth',
            'fourth year': 'Fourth',
            'forth': 'Fourth',
            'forth year': 'Fourth',
        };

        return map[normalized] || value || '';
    }

    function normalizedGradeLevel(value) {
        const normalized = String(value || '').trim().toLowerCase().replace(/[^a-z0-9]+/g, ' ').replace(/\s+/g, ' ').trim();
        const map = {
            '11': 'Eleven',
            'grade 11': 'Eleven',
            'eleven': 'Eleven',
            '12': 'Twelve',
            'grade 12': 'Twelve',
            'twelve': 'Twelve',
        };

        return map[normalized] || value || '';
    }

    function openEditVoterModal(button) {
        const overlay = document.getElementById('editVoterModalOverlay');
        const content = document.getElementById('editVoterModalContent');
        if (!overlay || !content) return;

        document.getElementById('edit_voter_id').value = button.dataset.voterId || '';
        document.getElementById('edit_sr_code').value = button.dataset.srCode || '';
        document.getElementById('edit_email').value = button.dataset.email || '';
        document.getElementById('edit_full_name').value = button.dataset.fullName || '';
        document.getElementById('edit_program').value = button.dataset.program || '';

        const college = document.getElementById('edit_voter_college');
        const year = document.getElementById('edit_year_level');
        const grade = document.getElementById('edit_grade_level');

        if (college) college.value = button.dataset.college || '';
        if (year) year.value = normalizedYearLevel(button.dataset.yearLevel);
        if (grade) grade.value = normalizedGradeLevel(button.dataset.gradeLevel);

        overlay.style.display = 'flex';
        setTimeout(() => {
            overlay.classList.add('is-open');
            content.classList.add('is-open');
        }, 10);
        document.body.style.overflow = 'hidden';
    }

    function closeEditVoterModal(event, force = false) {
        const overlay = document.getElementById('editVoterModalOverlay');
        const content = document.getElementById('editVoterModalContent');
        if (!overlay || !content) return;

        if (force || event.target === overlay) {
            overlay.classList.remove('is-open');
            content.classList.remove('is-open');
            setTimeout(() => {
                overlay.style.display = 'none';
            }, 300);
            document.body.style.overflow = '';
        }
    }

    function openImportModal() {
        const overlay = document.getElementById('voterImportModalOverlay');
        const content = document.getElementById('voterImportModalContent');
        if (!overlay || !content) return;

        overlay.style.display = 'flex';
        setTimeout(() => {
            overlay.classList.add('is-open');
            content.classList.add('is-open');
        }, 10);
        document.body.style.overflow = 'hidden';
    }

    function closeImportModal(event, force = false) {
        const overlay = document.getElementById('voterImportModalOverlay');
        const content = document.getElementById('voterImportModalContent');
        if (!overlay || !content) return;

        if (force || event.target === overlay) {
            overlay.classList.remove('is-open');
            content.classList.remove('is-open');
            setTimeout(() => {
                overlay.style.display = 'none';
            }, 300);
            document.body.style.overflow = '';
        }
    }

    const voterCsv = document.getElementById('voterCsv');
    const importBtn = document.getElementById('importBtn');

    if (voterCsv && importBtn) {
        voterCsv.addEventListener('change', function() {
            const hasFile = this.files && this.files.length > 0;
            importBtn.disabled = !hasFile;
            importBtn.classList.toggle('btn-brown', hasFile);
            importBtn.classList.toggle('btn-outline-brown', !hasFile);
        });
    }

    const saveVoterBtn = document.getElementById('saveVoterBtn');
    const manualFields = ['sr_code', 'email', 'full_name', 'voterCollege'].map(id => document.getElementById(id));

    function validateManualEntry() {
        if (!saveVoterBtn || manualFields.some(field => !field)) return;

        saveVoterBtn.disabled = !manualFields.every(field => field.value.trim() !== '');
    }

    manualFields.forEach(field => {
        if (!field) return;

        field.addEventListener('input', validateManualEntry);
        field.addEventListener('change', validateManualEntry);
    });

    validateManualEntry();

    let voterFilterTimer = null;
    let voterFilterRequestId = 0;
    let voterFilterChangeId = 0;

    function buildVoterFilterUrl(form) {
        const params = new URLSearchParams();
        const formData = new FormData(form);

        formData.forEach((value, key) => {
            const normalized = String(value).trim();

            if (normalized !== '') {
                params.set(key, normalized);
            }
        });

        return `${form.action}${params.toString() ? `?${params.toString()}` : ''}`;
    }

    async function loadVoterRegistry(url, historyMode = 'replace') {
        const currentPanel = document.querySelector('.voter-registry-panel');

        if (!currentPanel || !window.DOMParser) {
            window.location.href = url;
            return;
        }

        const activeElement = document.activeElement;
        const activeName = activeElement && currentPanel.contains(activeElement) && activeElement.name
            ? activeElement.name
            : null;
        const activeSelectionStart = activeElement && 'selectionStart' in activeElement ? activeElement.selectionStart : null;
        const activeSelectionEnd = activeElement && 'selectionEnd' in activeElement ? activeElement.selectionEnd : null;
        const requestId = ++voterFilterRequestId;
        const changeId = voterFilterChangeId;

        currentPanel.classList.add('is-loading');
        currentPanel.setAttribute('aria-busy', 'true');

        try {
            const response = await fetch(url, {
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) {
                throw new Error('Unable to load voter registry.');
            }

            const html = await response.text();
            const nextDocument = new DOMParser().parseFromString(html, 'text/html');
            const nextPanel = nextDocument.querySelector('.voter-registry-panel');

            if (!nextPanel) {
                throw new Error('Voter registry panel was not found.');
            }

            if (requestId !== voterFilterRequestId || changeId !== voterFilterChangeId) {
                currentPanel.classList.remove('is-loading');
                currentPanel.removeAttribute('aria-busy');
                return;
            }

            currentPanel.replaceWith(nextPanel);

            if (activeName) {
                const nextActiveElement = nextPanel.querySelector(`[name="${activeName.replace(/"/g, '\\"')}"]`);

                if (nextActiveElement) {
                    nextActiveElement.focus({ preventScroll: true });

                    if (
                        activeSelectionStart !== null
                        && activeSelectionEnd !== null
                        && typeof nextActiveElement.setSelectionRange === 'function'
                    ) {
                        nextActiveElement.setSelectionRange(activeSelectionStart, activeSelectionEnd);
                    }
                }
            }

            if (historyMode === 'push') {
                window.history.pushState({}, '', response.url || url);
            } else {
                window.history.replaceState({}, '', response.url || url);
            }
        } catch (error) {
            window.location.href = url;
        }
    }

    document.addEventListener('submit', function(event) {
        const form = event.target.closest('.voter-filter-bar');

        if (!form) return;

        event.preventDefault();
        voterFilterChangeId++;
        window.clearTimeout(voterFilterTimer);
        loadVoterRegistry(buildVoterFilterUrl(form), 'push');
    });

    document.addEventListener('input', function(event) {
        const input = event.target.closest('.voter-filter-bar input[name="sr_code"]');

        if (!input || !input.form) return;

        voterFilterChangeId++;
        window.clearTimeout(voterFilterTimer);
        voterFilterTimer = window.setTimeout(() => {
            loadVoterRegistry(buildVoterFilterUrl(input.form), 'replace');
        }, 350);
    });

    document.addEventListener('change', function(event) {
        const select = event.target.closest('.voter-filter-bar select[name="college"]');

        if (!select || !select.form) return;

        voterFilterChangeId++;
        window.clearTimeout(voterFilterTimer);
        loadVoterRegistry(buildVoterFilterUrl(select.form), 'replace');
    });

    document.addEventListener('click', function(event) {
        const link = event.target.closest('.voter-filter-reset');

        if (!link) return;

        event.preventDefault();
        voterFilterChangeId++;
        window.clearTimeout(voterFilterTimer);
        loadVoterRegistry(link.href, 'push');
    });

    window.addEventListener('popstate', function() {
        loadVoterRegistry(window.location.href, 'replace');
    });
</script>
