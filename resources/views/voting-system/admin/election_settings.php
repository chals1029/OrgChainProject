<?php
    $election = $election ?? [];
    $timezone = (string) ($timezone ?? 'Asia/Manila');
    $startDefault = (string) ($start_at_value ?? '');
    $endDefault = (string) ($end_at_value ?? '');
    $announcementExpiresDefault = (string) ($announcement_expires_at_value ?? '');

    $titleVal = trim((string) voting_old('title', (string) ($election['title'] ?? '')));
    $configuredStatusVal = (string) voting_old('status', (string) ($election['configured_status'] ?? $election['status'] ?? 'pending'));
    $effectiveStatusVal = (string) ($election['status'] ?? $configuredStatusVal);
    $startVal = (string) voting_old('start_at', $startDefault);
    $endVal = (string) voting_old('end_at', $endDefault);
    $announcementVal = (string) voting_old('announcement', trim((string) ($election['announcement'] ?? '')));
    $announcementExpiresVal = (string) voting_old('announcement_expires_at', $announcementExpiresDefault);
    $instructionsVal = (string) voting_old('instructions', trim((string) ($election['instructions'] ?? '')));
?>

<div class="admin-grid election-settings-grid">
    <section class="admin-panel election-settings-panel">
        <div class="panel-heading">
            <div>
                <p class="eyebrow">Schedule &amp; access</p>
                <h2>Election schedule</h2>
                <p class="text-muted small mb-0">Dates use the server timezone: <strong><?= e($timezone) ?></strong>. Use each field’s calendar and time controls (your browser’s date picker).</p>
            </div>
            <div class="d-flex flex-wrap gap-2 justify-content-end">
                <span class="dashboard-soft-pill <?= $configuredStatusVal === 'open' ? 'bg-success-subtle text-success' : ($configuredStatusVal === 'closed' ? 'bg-secondary-subtle' : '') ?>">
                    Configured: <?= e(ucfirst($configuredStatusVal)) ?>
                </span>
                <span class="dashboard-soft-pill <?= $effectiveStatusVal === 'open' ? 'bg-success-subtle text-success' : ($effectiveStatusVal === 'closed' ? 'bg-secondary-subtle' : '') ?>">
                    Effective now: <?= e(ucfirst($effectiveStatusVal)) ?>
                </span>
            </div>
        </div>

        <form method="post" action="<?= e(voting_url('/admin/election')) ?>" class="mt-3 election-settings-form">
            <?= voting_csrf_field() ?>

            <div class="election-settings-section">
                <div class="election-settings-section-heading">
                    <i class="bi bi-sliders"></i>
                    <div>
                        <h3>Election access</h3>
                        <p>Status, title, and official voting window.</p>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label" for="election_title">Election title</label>
                        <input class="form-control" id="election_title" name="title" value="<?= e($titleVal) ?>" required maxlength="255">
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="election_status">Voting access</label>
                        <select class="form-select" id="election_status" name="status" required>
                            <option value="pending" <?= $configuredStatusVal === 'pending' ? 'selected' : '' ?>>Pending (voting closed)</option>
                            <option value="open" <?= $configuredStatusVal === 'open' ? 'selected' : '' ?>>Open (voters may sign in &amp; vote)</option>
                            <option value="closed" <?= $configuredStatusVal === 'closed' ? 'selected' : '' ?>>Closed (voting finished)</option>
                        </select>
                        <small class="text-muted d-block mt-1">This status is an immediate override. Use <strong>Open</strong>, <strong>Pending</strong>, or <strong>Closed</strong> to control voter access right away without changing the saved schedule.</small>
                    </div>

                    <div class="col-12">
                        <span class="form-label d-block">Quick status</span>
                        <div class="btn-group flex-wrap" role="group" aria-label="Set status quickly">
                            <button type="button" class="btn btn-outline-success btn-sm" data-set-status="open">Open election</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" data-set-status="pending">Pending</button>
                            <button type="button" class="btn btn-outline-danger btn-sm" data-set-status="closed">Close election</button>
                        </div>
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="election_start_at"><i class="bi bi-calendar-event"></i> Start (date &amp; time)</label>
                        <input class="form-control" type="datetime-local" id="election_start_at" name="start_at" value="<?= e($startVal) ?>">
                        <small class="text-muted">Saved schedule reference. Quick status can override it without changing this time.</small>
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="election_end_at"><i class="bi bi-calendar-x"></i> End (date &amp; time)</label>
                        <input class="form-control" type="datetime-local" id="election_end_at" name="end_at" value="<?= e($endVal) ?>">
                        <small class="text-muted">Saved schedule reference. Quick status can override it without changing this time.</small>
                    </div>
                </div>
            </div>

            <div class="election-settings-section election-settings-section-announcement">
                <div class="election-settings-section-heading">
                    <i class="bi bi-megaphone-fill"></i>
                    <div>
                        <h3>Homepage announcement</h3>
                        <p>Floating notice shown below the public header.</p>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label" for="election_announcement">Announcement message</label>
                        <textarea class="form-control" id="election_announcement" name="announcement" rows="3" maxlength="2000" placeholder="Example: Server Maintenance"><?= e($announcementVal) ?></textarea>
                        <small class="text-muted">Leave blank to hide the announcement from the home screen.</small>
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="announcement_expires_at"><i class="bi bi-clock-history"></i> Expire announcement at</label>
                        <input class="form-control" type="datetime-local" id="announcement_expires_at" name="announcement_expires_at" value="<?= e($announcementExpiresVal) ?>">
                        <small class="text-muted">Optional. The public banner will show a countdown and disappear when this time passes.</small>
                    </div>
                </div>
            </div>

            <div class="election-settings-section">
                <div class="election-settings-section-heading">
                    <i class="bi bi-card-text"></i>
                    <div>
                        <h3>Public instructions</h3>
                        <p>Reusable election messaging across public pages.</p>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label" for="election_instructions">Public instructions / notice (optional)</label>
                        <textarea class="form-control" id="election_instructions" name="instructions" rows="4" maxlength="4000"><?= e($instructionsVal) ?></textarea>
                        <small class="text-muted">Shown or reused on the public site where election messaging appears.</small>
                    </div>
                </div>
            </div>

            <div class="d-flex flex-wrap gap-2 mt-4">
                <button type="submit" class="btn btn-brown px-4"><i class="bi bi-save"></i> Save election</button>
            </div>
        </form>

        <script>
            document.querySelectorAll('[data-set-status]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var v = btn.getAttribute('data-set-status');
                    var sel = document.getElementById('election_status');
                    if (sel && v) sel.value = v;
                });
            });
        </script>
    </section>
</div>
