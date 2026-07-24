<?php
    $election = $election ?? [];
    $defaults = [
        'kicker' => 'Election Overview',
        'heading' => 'Official Student Council Election',
        'body' => "The Campus Electoral Board officially opens the student council election process for verified voters. This page provides election information, voting reminders, and the official ballot for the current election cycle.\n\nEach phase is set to support order, fairness, and transparency for candidates and voters. Please review the instructions carefully before submitting your ballot.\n\nAs the election proceeds, students are encouraged to participate responsibly and follow all official guidance.",
    ];

    $kicker = trim((string) voting_old('ballot_card_kicker', trim((string) ($election['ballot_card_kicker'] ?? '')) ?: $defaults['kicker']));
    $heading = trim((string) voting_old('ballot_card_heading', trim((string) ($election['ballot_card_heading'] ?? '')) ?: $defaults['heading']));
    $body = trim((string) voting_old('ballot_card_body', trim((string) ($election['ballot_card_body'] ?? '')) ?: $defaults['body']));
    $imagePath = trim((string) ($election['ballot_card_image_path'] ?? ''));
    $imageUrl = $imagePath !== ''
        ? (preg_match('#^https?://#i', $imagePath) ? $imagePath : voting_asset($imagePath))
        : voting_asset('img/HirayaNew.jpg');
?>

<div class="admin-grid ballot-content-grid">
    <section class="admin-panel ballot-content-panel">
        <div class="panel-heading">
            <div>
                <p class="eyebrow">Public ballot</p>
                <h2>Ballot intro card</h2>
                <p class="text-muted small mb-0">This image and text appear on the voter ballot page and login interface. Upload the latest PR-approved pubmat here. Use two line breaks between paragraphs in the description.</p>
            </div>
        </div>

        <form method="post" action="<?= e(voting_url('/admin/ballot-content')) ?>" enctype="multipart/form-data" class="mt-3">
            <?= voting_csrf_field() ?>

            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label" for="ballot_card_kicker">Small label (above heading)</label>
                    <input class="form-control" id="ballot_card_kicker" name="ballot_card_kicker" value="<?= e($kicker) ?>" maxlength="255">
                </div>
                <div class="col-12">
                    <label class="form-label" for="ballot_card_image">Card image</label>
                    <input class="form-control" type="file" id="ballot_card_image" name="ballot_card_image" accept="image/jpeg,image/png">
                    <small class="text-muted">JPG or PNG, max 2 MB. <?= $imagePath !== '' ? 'Leave empty to keep current image.' : 'Required on first save.' ?></small>
                </div>
                <div class="col-12">
                    <label class="form-label" for="ballot_card_heading">Card heading</label>
                    <input class="form-control" id="ballot_card_heading" name="ballot_card_heading" value="<?= e($heading) ?>" maxlength="512" required>
                </div>
                <div class="col-12">
                    <label class="form-label" for="ballot_card_body">Description</label>
                    <textarea class="form-control" id="ballot_card_body" name="ballot_card_body" rows="10" required><?= e($body) ?></textarea>
                </div>
            </div>

            <div class="d-flex flex-wrap gap-2 mt-4">
                <button type="submit" class="btn btn-brown px-4"><i class="bi bi-save"></i> Save ballot card</button>
            </div>
        </form>
    </section>

    <section class="admin-panel ballot-content-panel">
        <div class="panel-heading">
            <div>
                <p class="eyebrow">Preview</p>
                <h2>Approximate voter view</h2>
            </div>
        </div>
        <section class="hiraya-ballot-brief ballot-admin-preview" aria-labelledby="ballotAdminPreviewTitle">
            <div class="hiraya-ballot-content">
                <div class="hiraya-ballot-copy">
                    <p class="hiraya-ballot-kicker"><i class="bi bi-stars"></i> <?= e($kicker) ?></p>
                    <h2 id="ballotAdminPreviewTitle"><?= e($heading) ?></h2>
                    <?php foreach (preg_split('/\R\R+/u', $body) ?: [] as $paragraph): ?>
                        <?php $paragraph = trim((string) $paragraph); ?>
                        <?php if ($paragraph !== ''): ?>
                            <p><?= nl2br(e($paragraph)) ?></p>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                <figure class="hiraya-ballot-poster">
                    <img src="<?= e($imageUrl) ?>" alt="Ballot card preview">
                </figure>
            </div>
        </section>
    </section>
</div>
