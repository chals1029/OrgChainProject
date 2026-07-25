<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(($title ?? 'Admin') . ' | OrgChain Voting') ?></title>
    <link rel="icon" type="image/png" sizes="256x256" href="<?= e(voting_asset('img/ssc-favicon.png')) ?>">
    <link rel="apple-touch-icon" href="<?= e(voting_asset('img/ssc-favicon.png')) ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700,800" rel="stylesheet">
    <link href="<?= e(voting_asset('css/app.css')) ?>" rel="stylesheet">
</head>
<body class="admin-shell ballot-global-layout">
    <?php
        $authUser = \App\VotingSystem\Core\Auth::user();
        $activePath = voting_current_path();
        $isAdminRole = ($authUser['role'] ?? '') === 'admin';
        $portalTitle = $isAdminRole ? 'Admin Portal' : 'Canvassing Portal';
        $portalSubtitle = $isAdminRole ? 'OrgChain Official Voting System' : 'OrgChain Canvassing System';
        $topbarSubtitle = $isAdminRole ? 'OrgChain Official Voting - Admin Portal' : 'OrgChain Official Voting - Canvassing Portal';
        /* Canvassing-only users use their Dashboard + tally + reports — skip duplicate "Canvassing" tally link for admins. */
        $navItems = [
            ['label' => 'Dashboard', 'icon' => 'bi-grid', 'url' => staff_dashboard_path_for($authUser), 'roles' => ['admin', 'canvassing', 'view_only']],
            ['label' => 'Election', 'icon' => 'bi-calendar-range', 'url' => '/admin/election', 'roles' => ['admin']],
            ['label' => 'Canvassing Account', 'icon' => 'bi-person-lock', 'url' => '/admin/canvassing-account', 'roles' => ['admin']],
            ['label' => 'Voter List', 'icon' => 'bi-people', 'url' => '/admin/voters', 'roles' => ['admin']],
            ['label' => 'Candidates', 'icon' => 'bi-person-badge', 'url' => '/admin/candidates', 'roles' => ['admin']],
            ['label' => 'Ballot card', 'icon' => 'bi-card-text', 'url' => '/admin/ballot-content', 'roles' => ['admin']],
            ['label' => 'Live Tally', 'icon' => 'bi-bar-chart-line', 'url' => canvassing_path(), 'roles' => ['canvassing', 'view_only']],
            ['label' => 'Reports', 'icon' => 'bi-printer', 'url' => staff_reports_path_for($authUser), 'roles' => ['admin', 'canvassing', 'view_only']],
            ['label' => 'Chain Verify', 'icon' => 'bi-link-45deg', 'url' => '/admin/chain-verify', 'roles' => ['admin', 'canvassing', 'view_only']],
            ['label' => 'Security', 'icon' => 'bi-shield-lock', 'url' => '/admin/security', 'roles' => ['admin']],
        ];
    ?>

    <header class="ballot-topbar no-print">
        <div class="topbar-left">
            <div class="topbar-logo-box">
                <img src="<?= e(voting_asset('img/orgchain-logo.png')) ?>" alt="OrgChain" class="ssc-logo-img" onerror="this.src='<?= e(voting_asset('img/orgchain-logo.png')) ?>'">
            </div>
            <div class="topbar-titles">
                <span class="sys-title">OrgChain Official Voting</span>
                <span class="sys-subtitle"><?= e($topbarSubtitle) ?></span>
            </div>
        </div>
        <div class="topbar-right">
            <span class="session-timer d-none d-lg-flex" style="margin-right: 1.5rem;"><i class="bi bi-clock"></i> <span id="sessionTimer">--:--:--</span></span>

            <div class="admin-profile-dropdown dropdown">
                <button type="button" class="admin-profile-btn dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" title="Account menu" aria-label="Open account menu">
                    <span class="admin-user-info d-none d-md-flex">
                    <span><?= e($authUser['name'] ?? 'Admin') ?></span>
                    <small><?= e(str_replace('_', ' ', $authUser['role'] ?? '')) ?></small>
                    </span>
                    <span class="admin-avatar-circle">
                        <i class="bi bi-person-circle"></i>
                    </span>
                </button>
                <div class="dropdown-menu dropdown-menu-end admin-profile-menu">
                    <div class="admin-profile-menu-header">
                        <strong><?= e($authUser['name'] ?? 'Admin') ?></strong>
                        <span><?= e(str_replace('_', ' ', $authUser['role'] ?? '')) ?></span>
                    </div>
                    <form method="post" action="<?= e(voting_url(admin_logout_path())) ?>" class="admin-profile-logout-form">
                        <?= voting_csrf_field() ?>
                        <button type="submit" class="dropdown-item admin-logout-item">
                            <i class="bi bi-box-arrow-right"></i> Sign out
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </header>

    <div class="ballot-split-content">
        <aside class="ballot-sidebar no-print">
            <div class="sidebar-header">
                <div class="sidebar-header-titles">
                    <h2><?= e($portalTitle) ?></h2>
                    <p><?= e($portalSubtitle) ?></p>
                </div>
                <button class="btn d-lg-none border-0 p-1 no-print" type="button" data-bs-toggle="collapse" data-bs-target="#adminSidebarCollapse" aria-expanded="false" aria-controls="adminSidebarCollapse" style="color: var(--ssc-ink);">
                    <i class="bi bi-list fs-2"></i>
                </button>
            </div>

            <!-- User identity moved to header dropdown -->

            <div class="collapse d-lg-flex flex-column flex-grow-1 w-100" id="adminSidebarCollapse">
                <nav class="jump-nav">
                    <p class="jump-nav-title"><i class="bi bi-menu-app"></i> MANAGEMENT</p>
                    <ul>
                        <?php foreach ($navItems as $item): ?>
                            <?php if (!in_array($authUser['role'] ?? '', $item['roles'], true)) { continue; } ?>
                            <?php $isActive = !str_contains($item['url'], '#') && $activePath === $item['url']; ?>
                            <li class="<?= $isActive ? 'filled' : '' ?>" onclick="window.location.href='<?= e(voting_url($item['url'])) ?>'">
                                <span class="jump-nav-name"><i class="bi <?= e($item['icon']) ?>"></i> <?= e($item['label']) ?></span>
                                <i class="bi bi-chevron-right jump-nav-arrow"></i>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </nav>

                <?php if ($isAdminRole): ?>
                <div class="sidebar-quick-guide">
                    <h4 class="guide-title"><i class="bi bi-lightbulb-fill"></i> ADMIN QUICK GUIDE</h4>
                    <div class="guide-list">
                        <div class="guide-step">
                            <div class="step-num">1</div>
                            <div class="step-content">
                                <strong>Voter List Management</strong>
                                <p>Upload and verify the official student database to ensure only eligible voters can access the system.</p>
                            </div>
                        </div>
                        <div class="guide-step">
                            <div class="step-num">2</div>
                            <div class="step-content">
                                <strong>Real-Time Oversight</strong>
                                <p>Monitor live participation rates and system security logs to proactively address technical hurdles.</p>
                            </div>
                        </div>
                        <div class="guide-step">
                            <div class="step-num">3</div>
                            <div class="step-content">
                                <strong>Automated Tabulation</strong>
                                <p>Lock the system at the deadline to instantly generate tamper-proof tallies and visual analytics.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div class="sidebar-quick-guide sidebar-quick-guide--canvass">
                    <h4 class="guide-title"><i class="bi bi-easel-fill"></i> CANVASSING PORTAL</h4>
                    <p class="small px-3 mb-0 pb-3" style="line-height:1.55; color: rgba(31,24,23,0.72);">Dashboard shows turnout and summaries. Live Tally is the granular filterable results view. Reports are print-ready canvass outputs.</p>
                </div>
                <?php endif; ?>


        </div>
    </aside>

        <div class="admin-main" style="flex-grow: 1; min-width: 0;">
            <?php require base_path('resources/views/voting-system/partials/flash.php'); ?>
            <main class="admin-content">
                <?= $content ?>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script src="<?= e(voting_asset('js/app.js')) ?>"></script>
</body>
</html>
