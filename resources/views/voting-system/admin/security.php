<?php
    $severityLabels = [
        'low' => 'Low',
        'medium' => 'Medium',
        'high' => 'High',
        'critical' => 'Critical',
    ];

    $formatIpAddress = static function (?string $ip): string {
        $ip = trim((string) $ip);

        if ($ip === '::1') {
            return 'Localhost (::1)';
        }

        if ($ip === '127.0.0.1') {
            return 'Localhost (127.0.0.1)';
        }

        return $ip !== '' ? $ip : 'Unknown';
    };

    $summarizeUserAgent = static function (?string $userAgent): string {
        $userAgent = trim((string) $userAgent);

        if ($userAgent === '') {
            return 'Unknown browser';
        }

        $browser = 'Browser';
        $version = '';

        if (preg_match('/Edg\/([0-9]+)/', $userAgent, $match) === 1) {
            $browser = 'Microsoft Edge';
            $version = $match[1];
        } elseif (preg_match('/Chrome\/([0-9]+)/', $userAgent, $match) === 1) {
            $browser = 'Chrome';
            $version = $match[1];
        } elseif (preg_match('/Firefox\/([0-9]+)/', $userAgent, $match) === 1) {
            $browser = 'Firefox';
            $version = $match[1];
        } elseif (preg_match('/Version\/([0-9.]+).*Safari\//', $userAgent, $match) === 1) {
            $browser = 'Safari';
            $version = $match[1];
        }

        $os = 'Unknown OS';

        if (stripos($userAgent, 'Windows NT 10.0') !== false) {
            $os = 'Windows 10/11';
        } elseif (stripos($userAgent, 'Windows') !== false) {
            $os = 'Windows';
        } elseif (stripos($userAgent, 'Android') !== false) {
            $os = 'Android';
        } elseif (stripos($userAgent, 'iPhone') !== false || stripos($userAgent, 'iPad') !== false) {
            $os = 'iOS';
        } elseif (stripos($userAgent, 'Mac OS X') !== false) {
            $os = 'macOS';
        } elseif (stripos($userAgent, 'Linux') !== false) {
            $os = 'Linux';
        }

        return trim($browser . ' ' . $version) . ' on ' . $os;
    };
?>

<div class="main-header-area">
    <div class="main-header-left">
        <p class="eyebrow"><i class="bi bi-shield-lock"></i> SECURITY MONITOR</p>
        <h1>Application Attack Tracking</h1>
        <p class="ssc-dashboard-tagline">App-layer request throttling, admin-route probes, and suspicious scanner visibility.</p>
    </div>
    <div class="main-header-right">
        <div class="progress-box">
            <span><i class="bi bi-speedometer"></i> Global Limit</span>
            <div class="progress-bar-small">
                <div class="progress-bar-fill-small" style="width: 100%; background-color: var(--ssc-orange);"></div>
            </div>
            <span><?= e($globalLimit) ?>/<?= e($globalWindow) ?>s</span>
        </div>
    </div>
</div>

<div class="dashboard-stat-grid security-stat-grid">
    <article class="dashboard-stat-card">
        <div class="stat-icon stat-status"><i class="bi bi-activity"></i></div>
        <div>
            <span>Security Events</span>
            <strong><?= e($summary['total'] ?? 0) ?></strong>
            <small>Last 24 hours</small>
        </div>
    </article>
    <article class="dashboard-stat-card">
        <div class="stat-icon stat-cast"><i class="bi bi-ban"></i></div>
        <div>
            <span>Blocked Requests</span>
            <strong><?= e($summary['blocked'] ?? 0) ?></strong>
            <small>Rate limits triggered</small>
        </div>
    </article>
    <article class="dashboard-stat-card">
        <div class="stat-icon stat-turnout"><i class="bi bi-exclamation-triangle"></i></div>
        <div>
            <span>High Risk</span>
            <strong><?= e($summary['high_risk'] ?? 0) ?></strong>
            <small>High or critical severity</small>
        </div>
    </article>
    <article class="dashboard-stat-card">
        <div class="stat-icon stat-voters"><i class="bi bi-router"></i></div>
        <div>
            <span>Unique IPs</span>
            <strong><?= e($summary['unique_ips'] ?? 0) ?></strong>
            <small>Observed sources</small>
        </div>
    </article>
</div>

<section class="admin-panel security-policy-panel">
    <div class="panel-heading">
        <div>
            <p class="eyebrow">Protection Rules</p>
            <h2>Active App-Layer Throttles</h2>
        </div>
        <span class="dashboard-soft-pill">Server firewall or CDN still recommended</span>
    </div>

    <div class="security-rule-grid">
        <div class="security-rule">
            <div class="rule-icon-box">
                <i class="bi bi-globe2"></i>
            </div>
            <div class="security-rule-content">
                <div class="rule-title-row">
                    <strong>Global request throttle</strong>
                    <span class="rule-status-badge">Active</span>
                </div>
                <span>Blocks an IP after <?= e($globalLimit) ?> requests in <?= e($globalWindow) ?> seconds.</span>
            </div>
        </div>
        <div class="security-rule">
            <div class="rule-icon-box">
                <i class="bi bi-key"></i>
            </div>
            <div class="security-rule-content">
                <div class="rule-title-row">
                    <strong>Sensitive route throttle</strong>
                    <span class="rule-status-badge">Active</span>
                </div>
                <span>Blocks an IP after <?= e($sensitiveLimit) ?> admin, vote, or Google-auth requests in <?= e($sensitiveWindow) ?> seconds.</span>
            </div>
        </div>
        <div class="security-rule">
            <div class="rule-icon-box">
                <i class="bi bi-search"></i>
            </div>
            <div class="security-rule-content">
                <div class="rule-title-row">
                    <strong>Probe detection</strong>
                    <span class="rule-status-badge">Active</span>
                </div>
                <span>Logs admin path guesses and common scanner paths like WordPress, phpMyAdmin, and storage URLs.</span>
            </div>
        </div>
        <div class="security-rule">
            <div class="rule-icon-box">
                <i class="bi bi-database-exclamation"></i>
            </div>
            <div class="security-rule-content">
                <div class="rule-title-row">
                    <strong>SQL injection detection</strong>
                    <span class="rule-status-badge">Active</span>
                </div>
                <span>Flags injection-style login input, records IP/browser details, and blocks repeated attempts.</span>
            </div>
        </div>
    </div>
</section>

<div class="dashboard-main-grid">
    <section class="admin-panel">
        <div class="panel-heading">
            <div>
                <p class="eyebrow">Top Sources</p>
                <h2>Most Active IPs</h2>
            </div>
        </div>
        <div class="security-source-list">
            <?php if (empty($topSources)): ?>
                <div class="empty-mini-state">
                    <i class="bi bi-shield-check" style="font-size: 2rem; display: block; margin-bottom: 0.5rem;"></i>
                    No suspicious IP activity recorded in the last 24 hours.
                </div>
            <?php endif; ?>
            <?php foreach ($topSources as $source): ?>
                <article class="security-source-item">
                    <div class="source-item-left">
                        <div class="source-icon-box">
                            <i class="bi bi-broadcast"></i>
                        </div>
                        <div class="source-info">
                            <strong><?= e($source['ip_address']) ?></strong>
                            <span>Last seen <?= e($source['last_seen']) ?></span>
                        </div>
                    </div>
                    <span class="source-event-badge"><?= e($source['event_count']) ?> events</span>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="admin-panel">
        <div class="panel-heading">
            <div>
                <p class="eyebrow">Response Notes</p>
                <h2>What This Can and Cannot Stop</h2>
            </div>
        </div>
        <div class="security-note-list">
            <p><i class="bi bi-check2-circle"></i> Helps stop rapid login attempts, route probes, scanner traffic, and basic app-layer floods.</p>
            <p><i class="bi bi-check2-circle"></i> Detects common SQL injection signatures before admin or canvassing authentication continues.</p>
            <p><i class="bi bi-check2-circle"></i> Gives admins IP, path, user agent, severity, and timestamps for incident review.</p>
            <p><i class="bi bi-info-circle"></i> True DDoS traffic should also be handled at the web server, firewall, hosting provider, or CDN level.</p>
        </div>
    </section>
</div>

<section class="admin-panel">
    <div class="panel-heading">
        <div>
            <p class="eyebrow">Event Log</p>
            <h2>Recent Security Events</h2>
        </div>
        <span class="dashboard-soft-pill"><?= e(count($recentEvents)) ?> latest</span>
    </div>

    <div class="table-responsive security-events-table" tabindex="0" aria-label="Scrollable security event log">
        <table class="table align-middle">
            <colgroup>
                <col class="security-col-time">
                <col class="security-col-severity">
                <col class="security-col-event">
                <col class="security-col-ip">
                <col class="security-col-method">
                <col class="security-col-path">
                <col class="security-col-count">
                <col class="security-col-agent">
            </colgroup>
            <thead>
                <tr>
                    <th>Time</th>
                    <th>Severity</th>
                    <th>Event</th>
                    <th>IP Address</th>
                    <th>Method</th>
                    <th>Path</th>
                    <th>Count</th>
                    <th>User Agent</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recentEvents)): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">No suspicious activity has been recorded yet.</td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($recentEvents as $event): ?>
                    <?php $severity = strtolower((string) ($event['severity'] ?? 'medium')); ?>
                    <tr>
                        <td><?= e($event['created_at']) ?></td>
                        <td><span class="security-severity is-<?= e($severity) ?>"><?= e($severityLabels[$severity] ?? ucfirst($severity)) ?></span></td>
                        <td>
                            <strong><?= e(str_replace('_', ' ', $event['event_type'])) ?></strong>
                            <?php if (!empty($event['details'])): ?>
                                <small><?= e($event['details']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td><code class="security-ip-address"><?= e($formatIpAddress($event['ip_address'] ?? '')) ?></code></td>
                        <td><?= e($event['method']) ?></td>
                        <td><code><?= e($event['path']) ?></code></td>
                        <td><?= e($event['request_count']) ?></td>
                        <td class="security-user-agent" title="<?= e($event['user_agent'] ?: 'Unknown') ?>">
                            <strong><?= e($summarizeUserAgent($event['user_agent'] ?? '')) ?></strong>
                            <small><?= e($event['user_agent'] ?: 'Unknown') ?></small>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
