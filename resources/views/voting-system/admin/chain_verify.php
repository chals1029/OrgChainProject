<?php
    $ok = is_array($result ?? null) ? (bool) ($result['ok'] ?? false) : null;
    $nodes = is_array($result['nodes'] ?? null) ? $result['nodes'] : [];
?>

<div class="main-header-area">
    <div class="main-header-left">
        <p class="eyebrow"><i class="bi bi-link-45deg"></i> VOTE CHAIN</p>
        <h1>Chain Integrity Verify</h1>
        <p class="ssc-dashboard-tagline">Check a ballot reference against the database receipt and all 3 local node ledgers.</p>
    </div>
</div>

<div class="admin-panel-card mb-4">
    <form method="get" action="<?= e(voting_url('/admin/chain-verify')) ?>" class="row g-3 align-items-end">
        <div class="col-md-8">
            <label for="reference" class="form-label">Ballot reference code</label>
            <input
                type="text"
                class="form-control"
                id="reference"
                name="reference"
                value="<?= e($reference ?? '') ?>"
                placeholder="SSC-YYYYMMDD-XXXXXX"
                required
                autocomplete="off"
            >
        </div>
        <div class="col-md-4">
            <button type="submit" class="btn btn-brown w-100">
                <i class="bi bi-shield-check"></i> Verify seal
            </button>
        </div>
    </form>
</div>

<?php if (is_array($result)): ?>
    <div class="admin-panel-card">
        <div class="d-flex align-items-center gap-2 mb-3">
            <?php if ($ok): ?>
                <span class="badge text-bg-success"><i class="bi bi-check-circle"></i> Verified</span>
            <?php else: ?>
                <span class="badge text-bg-danger"><i class="bi bi-x-circle"></i> Failed</span>
            <?php endif; ?>
            <strong><?= e((string) ($result['message'] ?? '')) ?></strong>
        </div>

        <?php if (!empty($result['receipt']) && is_array($result['receipt'])): ?>
            <dl class="row small mb-4">
                <dt class="col-sm-3">Reference</dt>
                <dd class="col-sm-9"><code><?= e((string) ($result['receipt']['reference_code'] ?? $reference)) ?></code></dd>
                <dt class="col-sm-3">Block hash</dt>
                <dd class="col-sm-9"><code class="user-select-all"><?= e((string) ($result['receipt']['block_hash'] ?? '—')) ?></code></dd>
                <dt class="col-sm-3">Previous hash</dt>
                <dd class="col-sm-9"><code class="user-select-all"><?= e((string) ($result['receipt']['previous_hash'] ?? '—')) ?></code></dd>
                <dt class="col-sm-3">Ballot root</dt>
                <dd class="col-sm-9"><code class="user-select-all"><?= e((string) ($result['receipt']['ballot_root'] ?? '—')) ?></code></dd>
                <dt class="col-sm-3">Hash link</dt>
                <dd class="col-sm-9"><?= !empty($result['hash_link_ok']) ? 'Intact' : 'Broken / unverified' ?></dd>
                <dt class="col-sm-3">Nodes matched</dt>
                <dd class="col-sm-9"><?= (int) ($result['nodes_matched'] ?? 0) ?> / 3</dd>
            </dl>
        <?php endif; ?>

        <?php if ($nodes !== []): ?>
            <h2 class="h5 mb-3">Node ledgers</h2>
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead>
                        <tr>
                            <th>Node</th>
                            <th>Status</th>
                            <th>Detail</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($nodes as $node): ?>
                            <tr>
                                <td>Node <?= (int) ($node['node'] ?? 0) ?></td>
                                <td>
                                    <?php
                                        $status = (string) ($node['status'] ?? '');
                                        $badge = $status === 'ok' ? 'success' : ($status === 'missing' ? 'warning' : 'danger');
                                    ?>
                                    <span class="badge text-bg-<?= e($badge) ?>"><?= e($status) ?></span>
                                </td>
                                <td><?= e((string) ($node['message'] ?? '')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>
