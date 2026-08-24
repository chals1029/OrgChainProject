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

<div class="admin-panel-card mt-4">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h2 class="h5 m-0"><i class="bi bi-code-square"></i> Blockchain REST API Explorer</h2>
            <p class="text-muted small m-0">Expose live 3-node blockchain integrity verification to external auditors, apps, and student clients.</p>
        </div>
        <span class="badge bg-primary">v1.0 REST API</span>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="p-3 border rounded bg-light">
                <strong class="d-block text-primary"><i class="bi bi-shield-check"></i> Verify Reference API</strong>
                <small class="text-muted d-block mb-2">Verify SHA-256 seal and 3-node consensus for a receipt reference.</small>
                <code>GET <?= e(voting_url('/api/blockchain/verify?reference=REF')) ?></code>
            </div>
        </div>
        <div class="col-md-4">
            <div class="p-3 border rounded bg-light">
                <strong class="d-block text-success"><i class="bi bi-cpu"></i> Ledger Status API</strong>
                <small class="text-muted d-block mb-2">Check block count, latest hash, genesis anchor, and 3-node health.</small>
                <code>GET <?= e(voting_url('/api/blockchain/status')) ?></code>
            </div>
        </div>
        <div class="col-md-4">
            <div class="p-3 border rounded bg-light">
                <strong class="d-block text-warning"><i class="bi bi-box-seam"></i> Block Detail API</strong>
                <small class="text-muted d-block mb-2">Inspect raw ledger block data by index or block hash.</small>
                <code>GET <?= e(voting_url('/api/blockchain/block?index=1')) ?></code>
            </div>
        </div>
    </div>

    <div class="card p-3 border-0 bg-dark text-white rounded">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <strong class="text-warning"><i class="bi bi-terminal"></i> Interactive API Response Tester</strong>
            <div class="btn-group btn-group-sm">
                <button type="button" class="btn btn-outline-light active" id="btnApiStatus">Ledger Status</button>
                <button type="button" class="btn btn-outline-light" id="btnApiVerify">Verify Ref</button>
                <button type="button" class="btn btn-outline-light" id="btnApiBlock">Block #1</button>
            </div>
        </div>
        <div class="input-group input-group-sm mb-3">
            <span class="input-group-text bg-secondary text-white border-secondary">Endpoint URL</span>
            <input type="text" class="form-control bg-secondary text-white border-secondary font-monospace" id="apiEndpointUrl" readonly value="<?= e(voting_url('/api/blockchain/status')) ?>">
            <button class="btn btn-primary" type="button" id="btnExecuteApi"><i class="bi bi-play-fill"></i> Execute API Call</button>
        </div>
        <pre class="bg-black text-success p-3 rounded font-monospace small mb-0" id="apiOutput" style="max-height: 280px; overflow-y: auto;">Click "Execute API Call" to test the live JSON response...</pre>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const urlInput = document.getElementById('apiEndpointUrl');
    const output = document.getElementById('apiOutput');
    const executeBtn = document.getElementById('btnExecuteApi');
    const baseUrl = '<?= e(voting_url('/api/blockchain/')) ?>';
    const sampleRef = '<?= e($reference ?: 'DEMO-REF-1234') ?>';

    document.getElementById('btnApiStatus')?.addEventListener('click', function() {
        document.querySelectorAll('.btn-group .btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        urlInput.value = baseUrl + 'status';
    });

    document.getElementById('btnApiVerify')?.addEventListener('click', function() {
        document.querySelectorAll('.btn-group .btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        urlInput.value = baseUrl + 'verify?reference=' + sampleRef;
    });

    document.getElementById('btnApiBlock')?.addEventListener('click', function() {
        document.querySelectorAll('.btn-group .btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        urlInput.value = baseUrl + 'block?index=1';
    });

    executeBtn?.addEventListener('click', async () => {
        const targetUrl = urlInput.value;
        output.textContent = '// Executing GET ' + targetUrl + ' ...';
        try {
            const res = await fetch(targetUrl);
            const data = await res.json();
            output.textContent = '// Status: ' + res.status + ' ' + res.statusText + '\n\n' + JSON.stringify(data, null, 2);
        } catch (err) {
            output.textContent = '// Error executing API call:\n' + err.message;
        }
    });
});
</script>
