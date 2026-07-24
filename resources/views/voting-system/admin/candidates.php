<div class="admin-grid" style="grid-template-columns: 1fr !important;">
    <section class="admin-panel">
        <div class="panel-heading">
            <div>
                <p class="eyebrow">Election Ballot</p>
                <h2>Current Candidate List</h2>
            </div>
            <div class="panel-actions">
                <button class="btn btn-brown btn-sm" type="button" onclick="openCandidateModal()">
                    <i class="bi bi-person-plus-fill"></i>
                    <span>Add Candidate</span>
                </button>
            </div>
        </div>

        <div class="position-filter-wrapper mb-4" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 0.75rem 1.25rem;">
            <div class="d-flex align-items-center gap-3">
                <div class="d-flex align-items-center gap-2" style="white-space: nowrap;">
                    <i class="bi bi-filter-left" style="color: #b06a24; font-size: 1.25rem;"></i>
                    <p class="filter-label m-0" style="font-weight: 800; color: #1e293b; font-size: 0.9rem;">Filter by Position</p>
                </div>
                <div class="flex-grow-1">
                    <select class="form-select form-select-sm" id="adminPositionFilter" onchange="filterAdminPositions(this.value)" style="border-radius: 8px; border: 1px solid #dee2e6; font-weight: 700; color: #1e293b; padding: 0.4rem 1rem;">
                        <option value="all">View All Positions</option>
                        <?php foreach (($positions ?? []) as $position): ?>
                            <option value="pos_<?= e($position['id']) ?>">
                                <?= e($position['title']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="candidate-admin-list-new">
            <?php foreach (($positions ?? []) as $position): ?>
                <div class="candidate-position-block mb-5" id="pos_<?= e($position['id']) ?>">
                    <h3 class="position-title-premium mb-3"><?= e($position['title']) ?></h3>

                    <div class="table-responsive">
                        <table class="table candidate-management-table align-middle">
                            <thead>
                                <tr>
                                    <th style="width: 72px;">Order</th>
                                    <th style="width: 88px;">Photo</th>
                                    <th>Candidate Name</th>
                                    <th>Party / Slate</th>
                                    <th class="text-end" style="min-width: 120px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($position['candidates'])): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">No candidates assigned to this position.</td>
                                    </tr>
                                <?php endif; ?>

                                <?php foreach ($position['candidates'] as $candidate): ?>
                                    <tr>
                                        <td>
                                            <span class="sort-order-badge"><?= e($candidate['sort_order']) ?></span>
                                        </td>
                                        <td>
                                            <div class="candidate-table-photo">
                                                <img src="<?= e(candidate_image_url($candidate)) ?>" alt="<?= e($candidate['name']) ?>">
                                            </div>
                                        </td>
                                        <td>
                                            <div class="candidate-table-identity">
                                                <strong><?= e($candidate['name']) ?></strong>
                                                <div class="small text-muted">ID <?= e((string) ($candidate['id'] ?? '')) ?></div>
                                            </div>
                                        </td>
                                        <td><?= e($candidate['party'] ?? '—') ?></td>
                                        <td class="text-end">
                                            <div class="candidate-row-actions justify-content-end">
                                                <button
                                                    class="btn btn-icon-only"
                                                    type="button"
                                                    title="Edit candidate"
                                                    data-candidate-id="<?= e((string) ($candidate['id'])) ?>"
                                                    data-position-id="<?= e((string) $position['id']) ?>"
                                                    data-name="<?= e($candidate['name']) ?>"
                                                    data-party="<?= e($candidate['party'] ?? '') ?>"
                                                    data-sort-order="<?= e((string) ($candidate['sort_order'] ?? '0')) ?>"
                                                    onclick="openEditCandidateModal(this)"
                                                >
                                                    <i class="bi bi-pencil-square"></i>
                                                </button>
                                                <button
                                                    class="btn btn-icon-only text-danger"
                                                    type="button"
                                                    title="Delete candidate"
                                                    data-delete-name="<?= e($candidate['name']) ?>"
                                                    data-delete-id="<?= e((string) ($candidate['id'])) ?>"
                                                    onclick="openDeleteCandidateModal(this)"
                                                >
                                                    <i class="bi bi-trash3"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Modal: Add Candidate -->
    <div class="voter-modal-overlay" id="candidateModalOverlay" onclick="closeCandidateModal(event)">
        <section class="admin-panel voter-modal-content" id="candidateModalContent" onclick="event.stopPropagation();">
            <div class="panel-heading">
                <div>
                    <p class="eyebrow">Candidate Management</p>
                    <h2>New Candidate Entry</h2>
                </div>
                <button type="button" class="btn-close-modal" onclick="closeCandidateModal(event, true)" aria-label="Close">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <form method="post" action="<?= e(voting_url('/admin/candidates')) ?>" class="mt-3" enctype="multipart/form-data">
                <?= voting_csrf_field() ?>
                <input type="hidden" name="_action" value="create">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label" for="position_id">Assigned Position</label>
                        <select class="form-select" id="position_id" name="position_id" required>
                            <option value="">Select position</option>
                            <?php foreach (($positions ?? []) as $position): ?>
                                <option value="<?= e((string) $position['id']) ?>"><?= e($position['title']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="name">Candidate Full Name</label>
                        <input class="form-control" id="name" name="name" value="<?= e(voting_old('name')) ?>" required placeholder="Juan Dela Cruz">
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="party">Party / Slate (optional)</label>
                        <input class="form-control" id="party" name="party" value="<?= e(voting_old('party')) ?>" placeholder="Lakas SSC">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Candidate Photo / Pubmat</label>
                        <div class="import-upload-zone photo-upload-zone" id="photoDropZone">
                            <i class="bi bi-image"></i>
                            <p>Click to browse or drag and drop candidate photo</p>
                            <span class="upload-hint">Accepted: JPG, PNG • Max: 2MB</span>
                            <input type="file" name="candidate_photo" id="candidatePhoto" accept="image/jpeg,image/png" required>
                            <div id="photoNameDisplay" class="mt-2 fw-bold text-success" style="display: none;"></div>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="sort_order">Display Order</label>
                        <input class="form-control" id="sort_order" type="number" name="sort_order" value="<?= e(voting_old('sort_order', '0')) ?>">
                    </div>
                </div>
                <div class="modal-footer-actions mt-4">
                    <button class="btn btn-outline-brown" type="button" onclick="closeCandidateModal(event, true)">Cancel</button>
                    <button class="btn btn-brown px-5" type="submit" id="saveCandidateBtn" disabled>
                        <i class="bi bi-plus-circle"></i> Save Candidate
                    </button>
                </div>
            </form>
        </section>
    </div>

    <!-- Modal: Edit Candidate -->
    <div class="voter-modal-overlay" id="editCandidateOverlay" onclick="closeEditCandidateModal(event)">
        <section class="admin-panel voter-modal-content" onclick="event.stopPropagation();">
            <div class="panel-heading">
                <div>
                    <p class="eyebrow">Candidate Management</p>
                    <h2>Edit Candidate</h2>
                </div>
                <button type="button" class="btn-close-modal" onclick="closeEditCandidateModal(null, true)" aria-label="Close">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <form method="post" action="<?= e(voting_url('/admin/candidates/update')) ?>" class="mt-3" enctype="multipart/form-data">
                <?= voting_csrf_field() ?>
                <input type="hidden" name="candidate_id" id="edit_candidate_id" value="">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label" for="edit_position_id">Assigned Position</label>
                        <select class="form-select" id="edit_position_id" name="position_id" required>
                            <?php foreach (($positions ?? []) as $position): ?>
                                <option value="<?= e((string) $position['id']) ?>"><?= e($position['title']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="edit_name">Candidate Full Name</label>
                        <input class="form-control" id="edit_name" name="name" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="edit_party">Party / Slate (optional)</label>
                        <input class="form-control" id="edit_party" name="party" placeholder="Lakas SSC">
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="edit_sort_order">Display Order</label>
                        <input class="form-control" id="edit_sort_order" type="number" name="sort_order" value="0">
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="edit_candidate_photo">Replace Photo (optional)</label>
                        <input type="file" name="candidate_photo" id="edit_candidate_photo" class="form-control" accept="image/jpeg,image/png">
                        <div class="form-text">JPG or PNG, max 2 MB. Leave empty to keep the current pubmat shown in the roster.</div>
                    </div>
                </div>
                <div class="modal-footer-actions mt-4">
                    <button class="btn btn-outline-brown" type="button" onclick="closeEditCandidateModal(null, true)">Cancel</button>
                    <button class="btn btn-brown px-5" type="submit"><i class="bi bi-save"></i> Save Changes</button>
                </div>
            </form>
        </section>
    </div>

    <!-- Modal: Delete Candidate -->
    <div class="voter-modal-overlay" id="deleteCandidateOverlay" onclick="closeDeleteCandidateModal(event)">
        <section class="admin-panel voter-modal-content" style="max-width: 440px;" onclick="event.stopPropagation();">
            <div class="panel-heading">
                <div>
                    <p class="eyebrow danger">Remove candidate</p>
                    <h2>Confirm deletion</h2>
                </div>
                <button type="button" class="btn-close-modal" onclick="closeDeleteCandidateModal(null, true)" aria-label="Close">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <p class="mt-3 text-muted mb-4" style="line-height:1.55;">This removes the nominee from this position tally. If ballots already reference them, votes for that candidate disappear from reports per database rules.</p>
            <p class="fw-bold" id="deleteCandidateNameShown"></p>
            <form method="post" action="<?= e(voting_url('/admin/candidates/delete')) ?>">
                <?= voting_csrf_field() ?>
                <input type="hidden" name="candidate_id" id="delete_candidate_id" value="">
                <div class="modal-footer-actions mt-4">
                    <button class="btn btn-outline-brown" type="button" onclick="closeDeleteCandidateModal(null, true)">Cancel</button>
                    <button class="btn btn-danger px-4" type="submit"><i class="bi bi-trash3"></i> Delete permanently</button>
                </div>
            </form>
        </section>
    </div>
</div>

<script>
    function openCandidateModal() {
        const overlay = document.getElementById('candidateModalOverlay');
        const content = document.getElementById('candidateModalContent');
        if (!overlay || !content) return;

        overlay.style.display = 'flex';
        setTimeout(() => {
            overlay.classList.add('is-open');
            content.classList.add('is-open');
        }, 10);
        document.body.style.overflow = 'hidden';
    }

    function closeCandidateModal(event, force = false) {
        const overlay = document.getElementById('candidateModalOverlay');
        const content = document.getElementById('candidateModalContent');
        if (!overlay || !content) return;

        if (force || (event && event.target === overlay)) {
            overlay.classList.remove('is-open');
            content.classList.remove('is-open');
            setTimeout(() => {
                overlay.style.display = 'none';
            }, 300);
            document.body.style.overflow = '';
        }
    }

    function openEditCandidateModal(button) {
        const overlay = document.getElementById('editCandidateOverlay');
        const content = overlay?.querySelector('.voter-modal-content');
        if (!overlay || !content) return;

        document.getElementById('edit_candidate_id').value = button.dataset.candidateId || '';
        document.getElementById('edit_name').value = button.dataset.name || '';
        document.getElementById('edit_party').value = button.dataset.party || '';
        document.getElementById('edit_sort_order').value = button.dataset.sortOrder || '0';
        const sel = document.getElementById('edit_position_id');
        if (sel) sel.value = button.dataset.positionId || '';

        const photoEdit = document.getElementById('edit_candidate_photo');
        if (photoEdit) photoEdit.value = '';

        overlay.style.display = 'flex';
        setTimeout(() => {
            overlay.classList.add('is-open');
            content.classList.add('is-open');
        }, 10);
        document.body.style.overflow = 'hidden';
    }

    function closeEditCandidateModal(event, force = false) {
        const overlay = document.getElementById('editCandidateOverlay');
        const content = overlay?.querySelector('.voter-modal-content');
        if (!overlay || !content) return;

        if (force || (event && event.target === overlay)) {
            overlay.classList.remove('is-open');
            content.classList.remove('is-open');
            setTimeout(() => {
                overlay.style.display = 'none';
            }, 300);
            document.body.style.overflow = '';
        }
    }

    function openDeleteCandidateModal(button) {
        const overlay = document.getElementById('deleteCandidateOverlay');
        const content = overlay?.querySelector('.voter-modal-content');
        if (!overlay || !content) return;

        document.getElementById('delete_candidate_id').value = button.dataset.deleteId || '';
        const label = button.dataset.deleteName || 'this nominee';
        const shown = document.getElementById('deleteCandidateNameShown');
        if (shown) shown.textContent = label;

        overlay.style.display = 'flex';
        setTimeout(() => {
            overlay.classList.add('is-open');
            content.classList.add('is-open');
        }, 10);
        document.body.style.overflow = 'hidden';
    }

    function closeDeleteCandidateModal(event, force = false) {
        const overlay = document.getElementById('deleteCandidateOverlay');
        const content = overlay?.querySelector('.voter-modal-content');
        if (!overlay || !content) return;

        if (force || (event && event.target === overlay)) {
            overlay.classList.remove('is-open');
            content.classList.remove('is-open');
            setTimeout(() => {
                overlay.style.display = 'none';
            }, 300);
            document.body.style.overflow = '';
        }
    }

    const candidatePhoto = document.getElementById('candidatePhoto');
    const photoNameDisplay = document.getElementById('photoNameDisplay');
    const photoDropZone = document.getElementById('photoDropZone');
    const saveCandidateBtn = document.getElementById('saveCandidateBtn');
    const candidateNameInput = document.getElementById('name');
    const candidatePositionSelect = document.getElementById('position_id');
    const editCandidatePhoto = document.getElementById('edit_candidate_photo');
    const candidatePhotoMaxBytes = 2 * 1024 * 1024;
    const candidatePhotoMaxMb = 2;

    function validateCandidateForm() {
        if (!saveCandidateBtn) return;

        const hasName = candidateNameInput && candidateNameInput.value.trim() !== '';
        const hasPosition = candidatePositionSelect && candidatePositionSelect.value !== '';
        const hasPhoto = candidatePhoto && candidatePhoto.files && candidatePhoto.files.length > 0;

        saveCandidateBtn.disabled = !(hasName && hasPosition && hasPhoto);
    }

    [candidateNameInput, candidatePositionSelect].forEach(el => {
        if (!el) return;
        el.addEventListener('input', validateCandidateForm);
        el.addEventListener('change', validateCandidateForm);
    });

    if (candidatePhoto && photoNameDisplay && photoDropZone) {
        candidatePhoto.addEventListener('change', function() {
            if (this.files && this.files.length > 0) {
                const file = this.files[0];
                const sizeMB = (file.size / (1024 * 1024)).toFixed(2);

                if (file.size > candidatePhotoMaxBytes) {
                    window.alert(`File is too large! Maximum size is ${candidatePhotoMaxMb}MB.`);
                    this.value = '';
                    photoNameDisplay.style.display = 'none';
                    photoDropZone.classList.remove('has-file');
                    validateCandidateForm();
                    return;
                }

                photoNameDisplay.textContent = `Selected: ${file.name} (${sizeMB} MB)`;
                photoNameDisplay.style.display = 'block';
                photoDropZone.classList.add('has-file');
            } else {
                photoNameDisplay.style.display = 'none';
                photoDropZone.classList.remove('has-file');
            }
            validateCandidateForm();
        });
    }

    if (editCandidatePhoto) {
        editCandidatePhoto.addEventListener('change', function() {
            if (!this.files || this.files.length === 0) return;

            if (this.files[0].size > candidatePhotoMaxBytes) {
                window.alert(`File is too large! Maximum size is ${candidatePhotoMaxMb}MB.`);
                this.value = '';
            }
        });
    }

    validateCandidateForm();

    window.filterAdminPositions = function(filter) {
        const blocks = document.querySelectorAll('.candidate-position-block');
        blocks.forEach(block => {
            if (filter === 'all' || block.id === filter) {
                block.style.display = 'block';
                block.classList.add('animate-fade-in');
            } else {
                block.style.display = 'none';
                block.classList.remove('animate-fade-in');
            }
        });
    };
</script>
