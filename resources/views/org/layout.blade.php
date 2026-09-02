<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') | {{ $brand['title'] }}</title>
    <link rel="icon" type="image/png" href="{{ asset('Orgchain logo.png') }}">

    @fonts

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700,800" rel="stylesheet">
        <link rel="stylesheet" href="{{ asset('css/landing.css') }}">
    @endif
    <link rel="stylesheet" href="{{ asset('css/org-portal.css') }}?v=14">
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

                {{-- Activities / Proposals / SDG Review / OVCAA Review --}}
                <a href="{{ route('office.activities') }}" class="org-nav-link {{ ($activeNav ?? '') === 'activities' ? 'is-active' : '' }}">
                    @if (($office->office_role ?? '') === 'oso')
                        <i class="bi bi-file-earmark-check-fill"></i>
                        <span>Proposals</span>
                    @elseif (($office->office_role ?? '') === 'sdo')
                        <i class="bi bi-leaf-fill"></i>
                        <span>SDG Document Review</span>
                    @elseif (($office->office_role ?? '') === 'ovcaa')
                        <i class="bi bi-patch-check-fill"></i>
                        <span>Final Approval</span>
                    @else
                        <i class="bi bi-lightning-charge-fill"></i>
                        <span>Activities</span>
                    @endif
                    <em class="org-badge-count">12</em>
                </a>

                <a href="{{ route('office.calendar') }}" class="org-nav-link {{ ($activeNav ?? '') === 'calendar' ? 'is-active' : '' }}">
                    <i class="bi bi-calendar3"></i>
                    <span>Calendar</span>
                    <em class="org-badge-count">3</em>
                </a>

                {{-- Budget Utilization: removed on OVCAA DESK ONLY --}}
                @if (($office->office_role ?? '') !== 'ovcaa')
                <a href="{{ route('office.budget') }}" class="org-nav-link {{ ($activeNav ?? '') === 'budget' ? 'is-active' : '' }}">
                    <i class="bi bi-wallet2"></i>
                    <span>Budget Utilization</span>
                </a>
                @endif

                {{-- Financial Report: removed on OVCAA and SDO --}}
                @if (!in_array(($office->office_role ?? ''), ['ovcaa', 'sdo']))
                <a href="{{ route('office.financial') }}" class="org-nav-link {{ ($activeNav ?? '') === 'financial' ? 'is-active' : '' }}">
                    <i class="bi bi-file-earmark-bar-graph"></i>
                    <span>Financial Report</span>
                    <em class="org-badge-count">{{ $navBadges['fr_attachments'] ?? 4 }}</em>
                </a>
                @endif

                {{-- Accomplishment Report: removed on OVCAA and SDO --}}
                @if (!in_array(($office->office_role ?? ''), ['ovcaa', 'sdo']))
                <a href="{{ route('office.accomplishment') }}" class="org-nav-link {{ ($activeNav ?? '') === 'accomplishment' ? 'is-active' : '' }}">
                    <i class="bi bi-trophy"></i>
                    <span>Accomplishment Report</span>
                    <em class="org-badge-count">{{ $navBadges['ar_attachments'] ?? 3 }}</em>
                </a>
                @endif

                <a href="{{ route('office.updates') }}" class="org-nav-link {{ ($activeNav ?? '') === 'updates' ? 'is-active' : '' }}">
                    <i class="bi bi-megaphone-fill"></i>
                    <span>Updates</span>
                    <em class="org-badge-count">3</em>
                </a>
                @if (($office->office_role ?? '') === 'oso')
                    <a href="{{ route('office.archive') }}" class="org-nav-link {{ ($activeNav ?? '') === 'archive' ? 'is-active' : '' }}">
                        <i class="bi bi-archive-fill"></i>
                        <span>Archive</span>
                    </a>
                @endif
            </nav>

            <div class="org-officer">
                <span class="org-officer-avatar" aria-hidden="true">{{ $office->initials() }}</span>
                <div class="org-officer-meta">
                    <strong>{{ $office->name }}</strong>
                    <span>{{ $brand['role'] }}</span>
                </div>
            </div>

            <form method="post" action="{{ route('office.logout') }}" class="org-logout" id="orgLogoutForm">
                @csrf
                <button type="button" onclick="openLogoutModal()">
                    <i class="bi bi-box-arrow-left"></i>
                    <span>Logout</span>
                </button>
            </form>
        </aside>

        <div class="org-main">
            <header class="org-topbar">
                <div>
                    <p class="org-module-kicker">
                        @if (($office->office_role ?? '') === 'oso')
                            BSU Office of Student Organizations (OSO) Review Desk
                        @elseif (($office->office_role ?? '') === 'sdo')
                            Sustainable Development Office (SDO) — SDG Alignment Review
                        @elseif (($office->office_role ?? '') === 'ovcaa')
                            OVCAA Final Approval Desk
                        @else
                            BSU Student Organization Module
                        @endif
                    </p>
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

    {{-- Logout Confirmation Modal --}}
    <div id="logoutConfirmModal" class="org-modal-overlay" style="display: none; position: fixed; inset: 0; background: rgba(0, 0, 0, 0.5); z-index: 99999; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
        <div class="org-modal-box" style="background: #ffffff; border-radius: 20px; border: 1.5px solid #f0e6e8; padding: 2rem; max-width: 420px; width: 90%; box-shadow: 0 20px 40px rgba(0,0,0,0.2); text-align: center;">
            <div style="width: 56px; height: 56px; border-radius: 50%; background: #fdf0f2; color: #8b1828; display: flex; align-items: center; justify-content: center; font-size: 1.75rem; margin: 0 auto 1.25rem;">
                <i class="bi bi-box-arrow-right"></i>
            </div>
            <h3 style="font-size: 1.25rem; font-weight: 800; color: #1a1618; margin: 0 0 0.5rem;">Confirm Logout</h3>
            <p style="font-size: 0.88rem; color: #635b5e; margin: 0 0 1.5rem; line-height: 1.5;">
                Are you sure you want to log out of your OrgChain session? Any unsaved changes will be lost.
            </p>
            <div style="display: flex; gap: 0.75rem; justify-content: center;">
                <button type="button" onclick="closeLogoutModal()" style="flex: 1; padding: 0.75rem 1.25rem; border-radius: 9999px; border: 1.5px solid #e8dedf; background: #ffffff; font-weight: 700; font-size: 0.88rem; color: #554d50; cursor: pointer;">
                    Cancel
                </button>
                <button type="button" onclick="document.getElementById('orgLogoutForm').submit()" style="flex: 1; padding: 0.75rem 1.25rem; border-radius: 9999px; border: none; background: #8b1828; font-weight: 700; font-size: 0.88rem; color: #ffffff; cursor: pointer; box-shadow: 0 4px 14px rgba(139, 24, 40, 0.25);">
                    Yes, Log Out
                </button>
            </div>
        </div>
    </div>

    <script>
        function openLogoutModal() {
            const modal = document.getElementById('logoutConfirmModal');
            if (modal) {
                modal.style.display = 'flex';
            }
        }
        function closeLogoutModal() {
            const modal = document.getElementById('logoutConfirmModal');
            if (modal) {
                modal.style.display = 'none';
            }
        }
    </script>
    @stack('scripts')
</body>
</html>
