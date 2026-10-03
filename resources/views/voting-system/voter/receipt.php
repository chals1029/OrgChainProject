<section class="compact-page">
    <div class="container">
        <?php include dirname(__DIR__) . '/partials/flash.php'; ?>

        <div class="empty-state receipt-state">
            <i class="bi bi-check-circle-fill"></i>
            <p class="eyebrow">Vote Submitted</p>
            <h1>Thank you for voting.</h1>
            <p>Your ballot was received, integrity-sealed, and your voter record has been marked as voted.</p>
            <div class="receipt-code"><?= e($reference) ?></div>



            <?php if (!empty($receipt['block_hash'])): ?>
                <?php
                $chainDriver = strtolower((string) ($receipt['chain_driver'] ?? 'file'));
                $validatorTotal = $chainDriver === 'besu' ? (int) config('besu.validator_count', 4) : 3;
                $nodesConfirmed = (int) ($receipt['nodes_confirmed'] ?? 0);
                $chainVerified = $nodesConfirmed >= $validatorTotal;
                $networkLabel = $chainDriver === 'besu' ? 'Besu QBFT' : 'OrgChain ledger';
                ?>
                <section class="public-proof-card <?= $chainVerified ? 'is-verified' : 'is-recorded' ?> mt-4 text-start" aria-labelledby="publicProofTitle">
                    <div class="proof-card-orbit" aria-hidden="true"></div>
                    <div class="proof-card-topline">
                        <div class="proof-card-heading">
                            <span class="proof-card-icon" aria-hidden="true"><i class="bi bi-shield-check"></i></span>
                            <div>
                                <p class="proof-card-kicker">Public blockchain proof</p>
                                <h2 id="publicProofTitle">Ballot integrity seal</h2>
                            </div>
                        </div>
                        <span class="proof-card-status">
                            <span class="proof-status-dot" aria-hidden="true"></span>
                            <?= $chainVerified ? 'Verified' : 'Recorded' ?>
                        </span>
                    </div>

                    <p class="proof-card-summary">
                        This public hash lets anyone check the receipt's integrity without exposing your identity or selected candidates in the proof.
                    </p>

                    <div class="proof-hash-panel">
                        <div class="proof-hash-label">
                            <span>Block hash</span>
                            <span class="proof-hash-algorithm">SHA-256</span>
                        </div>
                        <code class="proof-hash-value user-select-all" title="Full public block hash"><?= e($receipt['block_hash']) ?></code>
                        <button type="button" class="proof-copy-button" data-copy-proof-hash="<?= e($receipt['block_hash']) ?>" aria-label="Copy public block hash">
                            <i class="bi bi-copy" aria-hidden="true"></i>
                            <span data-copy-proof-label>Copy hash</span>
                        </button>
                    </div>

                    <div class="proof-card-metrics" aria-label="Blockchain proof status">
                        <div>
                            <span>Network</span>
                            <strong><?= e($networkLabel) ?></strong>
                        </div>
                        <div>
                            <span>Validators</span>
                            <strong><?= $nodesConfirmed ?> / <?= $validatorTotal ?></strong>
                        </div>
                        <div>
                            <span>Visibility</span>
                            <strong>Public proof</strong>
                        </div>
                    </div>

                    <details class="proof-chain-details">
                        <summary>View chain details</summary>
                        <dl class="receipt-chain-meta small mb-0">
                            <dt>Previous hash</dt>
                            <dd><code class="user-select-all"><?= e($receipt['previous_hash'] ?? '') ?></code></dd>
                            <dt>Ballot data</dt>
                            <dd>Kept private</dd>
                            <dt>Chain driver</dt>
                            <dd><?= e($networkLabel) ?></dd>
                        </dl>
                    </details>
                </section>
            <?php endif; ?>

            <a href="<?= e(voting_url('/')) ?>" class="btn btn-brown mt-4">Return home</a>
        </div>
    </div>
</section>
