<section class="compact-page">
    <div class="container">
        <div class="empty-state receipt-state">
            <i class="bi bi-check-circle-fill"></i>
            <p class="eyebrow">Vote Submitted</p>
            <h1>Thank you for voting.</h1>
            <p>Your ballot was received, sealed on the local 3-node chain, and your voter record has been marked as voted.</p>
            <div class="receipt-code"><?= e($reference) ?></div>

            <?php if (!empty($receipt['block_hash'])): ?>
                <div class="receipt-chain mt-4 text-start" style="max-width: 36rem; margin-inline: auto;">
                    <p class="eyebrow mb-2"><i class="bi bi-link-45deg"></i> Chain seal</p>
                    <dl class="receipt-chain-meta small mb-0">
                        <dt>Block hash</dt>
                        <dd><code class="user-select-all"><?= e($receipt['block_hash']) ?></code></dd>
                        <dt>Previous hash</dt>
                        <dd><code class="user-select-all"><?= e($receipt['previous_hash'] ?? '') ?></code></dd>
                        <dt>Ballot root</dt>
                        <dd><code class="user-select-all"><?= e($receipt['ballot_root'] ?? '') ?></code></dd>
                        <dt>Nodes confirmed</dt>
                        <dd><?= (int) ($receipt['nodes_confirmed'] ?? 0) ?> / 3</dd>
                    </dl>
                </div>
            <?php endif; ?>

            <a href="<?= e(voting_url('/')) ?>" class="btn btn-brown mt-4">Return home</a>
        </div>
    </div>
</section>
