<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') | {{ $brand['title'] }}</title>

    @fonts

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700,800" rel="stylesheet">
        <link rel="stylesheet" href="{{ asset('css/landing.css') }}">
    @endif
    <link rel="stylesheet" href="{{ asset('css/org-portal.css') }}?v=9">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="org-body">
    <div class="org-ambient" aria-hidden="true">
        <span class="org-blob org-blob-a"></span>
        <span class="org-blob org-blob-b"></span>
        <span class="org-blob org-blob-c"></span>
    </div>

    <div class="org-shell">
        <aside class="org-sidebar liquid-glass">
            <div class="org-sidebar-brand">
                <div class="org-brand-mark" aria-hidden="true">
                    <i class="bi bi-people-fill"></i>
                </div>
                <div>
                    <strong>{{ $brand['title'] }}</strong>
                    <span>{{ $brand['role'] }}</span>
                </div>
            </div>

            <p class="org-nav-section">Menu</p>
            <nav class="org-nav" aria-label="Student Org">
                <a href="{{ route('office.home') }}" class="org-nav-link {{ ($activeNav ?? '') === 'dashboard' ? 'is-active' : '' }}">
                    <i class="bi bi-grid-1x2-fill"></i>
                    <span>Dashboard</span>
                    @if (($activeNav ?? '') === 'dashboard')
                        <em class="org-badge-new">New</em>
                    @endif
                </a>
                <a href="{{ route('office.analytics') }}" class="org-nav-link {{ ($activeNav ?? '') === 'analytics' ? 'is-active' : '' }}">
                    <i class="bi bi-bar-chart-fill"></i>
                    <span>Analytics</span>
                </a>
                <a href="{{ route('office.activities') }}" class="org-nav-link {{ ($activeNav ?? '') === 'activities' ? 'is-active' : '' }}">
                    <i class="bi bi-lightning-charge-fill"></i>
                    <span>Activities</span>
                    <em class="org-badge-count">12</em>
                </a>
                <a href="{{ route('office.calendar') }}" class="org-nav-link {{ ($activeNav ?? '') === 'calendar' ? 'is-active' : '' }}">
                    <i class="bi bi-calendar3"></i>
                    <span>Calendar</span>
                    <em class="org-badge-count">3</em>
                </a>
                <a href="{{ route('office.budget') }}" class="org-nav-link {{ ($activeNav ?? '') === 'budget' ? 'is-active' : '' }}">
                    <i class="bi bi-wallet2"></i>
                    <span>Budget Utilization</span>
                </a>
                <a href="{{ route('office.financial') }}" class="org-nav-link {{ ($activeNav ?? '') === 'financial' ? 'is-active' : '' }}">
                    <i class="bi bi-file-earmark-bar-graph"></i>
                    <span>Financial Report</span>
                    <em class="org-badge-count">{{ $navBadges['fr_attachments'] ?? 4 }}</em>
                </a>
                <a href="{{ route('office.accomplishment') }}" class="org-nav-link {{ ($activeNav ?? '') === 'accomplishment' ? 'is-active' : '' }}">
                    <i class="bi bi-trophy"></i>
                    <span>Accomplishment Report</span>
                    <em class="org-badge-count">{{ $navBadges['ar_attachments'] ?? 3 }}</em>
                </a>
            </nav>

            <div class="org-officer">
                <span class="org-officer-avatar" aria-hidden="true">{{ $office->initials() }}</span>
                <div class="org-officer-meta">
                    <strong>{{ $office->name }}</strong>
                    <span>{{ $brand['role'] }}</span>
                </div>
            </div>

            <form method="post" action="{{ route('office.logout') }}" class="org-logout">
                @csrf
                <button type="submit">
                    <i class="bi bi-box-arrow-left"></i>
                    <span>Logout</span>
                </button>
            </form>
        </aside>

        <div class="org-main">
            <header class="org-topbar">
                <div>
                    <p class="org-module-kicker">BSU Student Organization Module</p>
                    @yield('header')
                </div>
                <div class="org-top-actions">
                    @yield('actions')
                    <button type="button" class="org-bell" aria-label="Notifications">
                        <i class="bi bi-bell-fill"></i>
                        <span class="org-bell-dot"></span>
                    </button>
                </div>
            </header>

            <div class="org-content">
                @yield('content')
            </div>
        </div>
    </div>
</body>
</html>
