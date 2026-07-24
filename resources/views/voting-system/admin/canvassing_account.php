<?php
    $accounts = $accounts ?? [];
    $oldName = (string) voting_old('name', '');
    $oldEmail = (string) voting_old('email', '');
    $oldRole = (string) voting_old('role', 'canvassing');
?>

<div class="main-header-area">
    <div class="main-header-left">
        <p class="eyebrow"><i class="bi bi-person-lock"></i> Staff Access</p>
        <h1>Canvassing Account</h1>
        <p class="ssc-dashboard-tagline">Create and manage staff accounts for live tally, canvassing dashboard, and reports access.</p>
    </div>
</div>

<div class="admin-grid election-settings-grid">
    <section class="admin-panel">
        <div class="panel-heading">
            <div>
                <p class="eyebrow">New account</p>
                <h2>Add canvassing access</h2>
                <p class="text-muted small mb-0">Use <strong>Canvassing</strong> for tally/report access, or <strong>View only</strong> for read-only monitoring. A strong password will be generated and emailed to the staff member.</p>
            </div>
        </div>

        <form method="post" action="<?= e(voting_url('/admin/canvassing-account')) ?>" class="row g-3">
            <?= voting_csrf_field() ?>
            <div class="col-md-6">
                <label class="form-label" for="staff_name">Name</label>
                <input class="form-control" id="staff_name" name="name" value="<?= e($oldName) ?>" maxlength="255" required>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="staff_email">Email</label>
                <input class="form-control" id="staff_email" name="email" type="email" value="<?= e($oldEmail) ?>" maxlength="255" required>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="staff_role">Role</label>
                <select class="form-select" id="staff_role" name="role" required>
                    <option value="canvassing" <?= $oldRole === 'canvassing' ? 'selected' : '' ?>>Canvassing</option>
                    <option value="view_only" <?= $oldRole === 'view_only' ? 'selected' : '' ?>>View only</option>
                </select>
            </div>
            <div class="col-md-6">
                <span class="form-label d-block">Password delivery</span>
                <div class="alert alert-info mb-0 py-2">
                    <i class="bi bi-shield-lock"></i> The system will generate a strong password and email it with a privacy reminder.
                </div>
            </div>
            <div class="col-12">
                <button class="btn btn-brown px-4" type="submit"><i class="bi bi-person-plus"></i> Add account</button>
            </div>
        </form>
    </section>
</div>

<section class="admin-panel mt-3">
    <div class="panel-heading">
        <div>
            <p class="eyebrow">Existing accounts</p>
            <h2>Canvassing staff</h2>
        </div>
    </div>

    <?php if (empty($accounts)): ?>
        <div class="alert alert-warning mb-0">
            <i class="bi bi-exclamation-triangle"></i> No canvassing accounts yet.
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Reset Password</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($accounts as $account): ?>
                        <?php $formId = 'canvassingAccountForm' . (int) $account['id']; ?>
                        <tr>
                            <td style="min-width: 180px;">
                                <form id="<?= e($formId) ?>" method="post" action="<?= e(voting_url('/admin/canvassing-account/update')) ?>">
                                    <?= voting_csrf_field() ?>
                                    <input type="hidden" name="account_id" value="<?= e($account['id']) ?>">
                                </form>
                                <input class="form-control form-control-sm" form="<?= e($formId) ?>" name="name" value="<?= e($account['name']) ?>" maxlength="255" required>
                            </td>
                            <td style="min-width: 230px;">
                                <input class="form-control form-control-sm" form="<?= e($formId) ?>" name="email" type="email" value="<?= e($account['email']) ?>" maxlength="255" required>
                            </td>
                            <td style="min-width: 145px;">
                                <select class="form-select form-select-sm" form="<?= e($formId) ?>" name="role" required>
                                    <option value="canvassing" <?= ($account['role'] ?? '') === 'canvassing' ? 'selected' : '' ?>>Canvassing</option>
                                    <option value="view_only" <?= ($account['role'] ?? '') === 'view_only' ? 'selected' : '' ?>>View only</option>
                                </select>
                            </td>
                            <td style="min-width: 105px;">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" form="<?= e($formId) ?>" type="checkbox" role="switch" name="is_active" value="1" <?= (int) ($account['is_active'] ?? 0) === 1 ? 'checked' : '' ?> aria-label="Account active">
                                    <span class="badge <?= (int) ($account['is_active'] ?? 0) === 1 ? 'text-bg-success' : 'text-bg-secondary' ?>">
                                        <?= (int) ($account['is_active'] ?? 0) === 1 ? 'Active' : 'Disabled' ?>
                                    </span>
                                </div>
                            </td>
                            <td style="min-width: 190px;">
                                <input class="form-control form-control-sm" form="<?= e($formId) ?>" name="password" type="password" minlength="8" autocomplete="new-password" placeholder="Leave unchanged">
                            </td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-2">
                                    <button class="btn btn-outline-success btn-sm" form="<?= e($formId) ?>" type="submit"><i class="bi bi-save"></i> Save</button>
                                    <form method="post" action="<?= e(voting_url('/admin/canvassing-account/delete')) ?>" onsubmit="return confirm('Delete this canvassing account? This cannot be undone.');">
                                        <?= voting_csrf_field() ?>
                                        <input type="hidden" name="account_id" value="<?= e($account['id']) ?>">
                                        <button class="btn btn-outline-danger btn-sm" type="submit"><i class="bi bi-trash"></i> Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
