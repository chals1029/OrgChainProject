<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Maintenance Console') | OrgChain</title>
    <link rel="icon" type="image/png" href="{{ asset('Orgchain logo.png') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700,800" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/system-admin.css') }}?v=2">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="sys-admin-body">
    <div class="sys-admin-shell">
        <aside class="sys-admin-sidebar" id="sysAdminSidebar">
            <div class="sys-brand">
                <div class="sys-brand-mark"><i class="bi bi-shield-lock-fill"></i></div>
                <div>
                    <strong>OrgChain</strong>
                    <span>System Maintenance</span>
                </div>
            </div>

            <div class="sys-sidebar-rule"></div>
            <p class="sys-nav-kicker">Control plane</p>
            <nav class="sys-nav" aria-label="System maintenance">
                <a class="sys-nav-link is-active" href="{{ route('system-admin.dashboard') }}">
                    <i class="bi bi-speedometer2"></i><span>System overview</span>
                </a>
                <a class="sys-nav-link" href="{{ route('system-admin.dashboard') }}#officeAccounts">
                    <i class="bi bi-person-gear"></i><span>Office access</span>
                </a>
                <a class="sys-nav-link" href="{{ route('system-admin.dashboard') }}#auditTrail">
                    <i class="bi bi-journal-check"></i><span>Audit trail</span>
                </a>
            </nav>

            <div class="sys-sidebar-bottom">
                <div class="sys-admin-identity">
                    <div class="sys-avatar">{{ $admin->initials() }}</div>
                    <div>
                        <strong>{{ $admin->name }}</strong>
                        <span>{{ $admin->roleLabel() }}</span>
                    </div>
                </div>
                <form method="post" action="{{ route('system-admin.logout') }}">
                    @csrf
                    <button type="submit" class="sys-logout-btn"><i class="bi bi-box-arrow-left"></i> Sign out</button>
                </form>
            </div>
        </aside>

        <main class="sys-admin-main">
            <header class="sys-admin-topbar">
                <div class="sys-mobile-title">
                    <button type="button" class="sys-mobile-menu" aria-label="Open maintenance menu" onclick="document.getElementById('sysAdminSidebar').classList.toggle('is-open')"><i class="bi bi-list"></i></button>
                    <div><strong>System Maintenance</strong><span>OrgChain control plane</span></div>
                </div>
                <div class="sys-topbar-meta">
                    <span class="sys-secure-badge"><i class="bi bi-shield-check"></i> Protected session</span>
                    <span class="sys-topbar-user">{{ $admin->email }}</span>
                </div>
            </header>

            <div class="sys-admin-content">
                @if (session('success') || session('error'))
                    <div class="sys-alert {{ session('error') ? 'sys-alert-error' : 'sys-alert-success' }}" role="alert">
                        <i class="bi {{ session('error') ? 'bi-exclamation-triangle-fill' : 'bi-check-circle-fill' }}"></i>
                        <span>{{ session('success') ?? session('error') }}</span>
                    </div>
                @endif
                @yield('content')
            </div>
        </main>
    </div>
</body>
</html>
