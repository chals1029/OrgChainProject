<?php
    $reportsPath = canvassing_reports_path();
?>

<div class="main-header-area">
    <div class="main-header-left">
        <p class="eyebrow"><i class="bi bi-shield-lock"></i> RESTRICTED ACCESS</p>
        <h1>Reports PIN Required</h1>
        <p class="ssc-dashboard-tagline">Enter the authorized PIN to access the printable election reports.</p>
    </div>
</div>

<section class="admin-panel" style="max-width: 480px; margin: 2rem auto; padding: 2.5rem;">
    <?php $flashError = voting_flash('error'); ?>
    <?php if ($flashError): ?>
        <div class="alert alert-danger" style="background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; padding: 0.75rem 1rem; border-radius: 10px; margin-bottom: 1.5rem; font-weight: 600;">
            <i class="bi bi-exclamation-triangle-fill"></i> <?= e($flashError) ?>
        </div>
    <?php endif; ?>

    <form action="<?= e(voting_url($reportsPath)) ?>" method="POST" style="display: flex; flex-direction: column; gap: 1.25rem;">
        <?= voting_csrf_field() ?>

        <div style="text-align: center; margin-bottom: 0.5rem;">
            <div style="width: 64px; height: 64px; margin: 0 auto 1rem; background: #fffbeb; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                <i class="bi bi-lock-fill" style="font-size: 1.75rem; color: #b06a24;"></i>
            </div>
            <h2 style="margin: 0 0 0.25rem; font-size: 1.25rem; color: #1e293b;">Enter Access PIN</h2>
            <p style="margin: 0; color: #64748b; font-size: 0.9rem;">This PIN was provided by the election administrator.</p>
        </div>

        <div>
            <label for="reportPin" style="display: block; font-weight: 700; margin-bottom: 0.4rem; color: #374151; font-size: 0.9rem;">PIN</label>
            <input
                type="password"
                id="reportPin"
                name="pin"
                required
                autocomplete="off"
                placeholder="Enter PIN"
                style="width: 100%; padding: 0.75rem 1rem; border: 2px solid #e5e7eb; border-radius: 10px; font-size: 1rem; transition: border-color 0.2s;"
                onfocus="this.style.borderColor='#b06a24'"
                onblur="this.style.borderColor='#e5e7eb'"
            >
        </div>

        <button type="submit" class="btn btn-gold" style="width: 100%; padding: 0.85rem; font-size: 1rem; font-weight: 800; border-radius: 10px;">
            <i class="bi bi-unlock-fill"></i> Unlock Reports
        </button>
    </form>
</section>
