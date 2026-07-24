<section class="admin-panel">
    <div class="empty-state">
        <p class="eyebrow">403</p>
        <h2>Access Restricted</h2>
        <p>Your role does not have access to this module.</p>
        <a href="<?= e(voting_url(staff_dashboard_path_for(\App\VotingSystem\Core\Auth::user()))) ?>" class="btn btn-brown">Back to dashboard</a>
    </div>
</section>
