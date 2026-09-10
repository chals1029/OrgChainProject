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
        <aside class="org-sidebar liquid-glass" id="orgSidebar">
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
                    <i class="bi bi-grid-1x2-fill is-ico-red"></i>
                    <span>Dashboard</span>
                    @if (($activeNav ?? '') === 'dashboard')
                        <em class="org-badge-new">New</em>
                    @endif
                </a>
                <a href="{{ route('office.analytics') }}" class="org-nav-link {{ ($activeNav ?? '') === 'analytics' ? 'is-active' : '' }}">
                    <i class="bi bi-bar-chart-fill is-ico-violet"></i>
                    <span>Analytics</span>
                </a>

                {{-- Activities / Proposals / SDG Review / OVCAA Review --}}
                <a href="{{ route('office.activities') }}" class="org-nav-link {{ ($activeNav ?? '') === 'activities' ? 'is-active' : '' }}">
                    @if (($office->office_role ?? '') === 'oso')
                        <i class="bi bi-file-earmark-check-fill is-ico-blue"></i>
                        <span>Proposals</span>
                    @elseif (($office->office_role ?? '') === 'sdo')
                        <i class="bi bi-leaf-fill is-ico-green"></i>
                        <span>SDG Document Review</span>
                    @elseif (($office->office_role ?? '') === 'ovcaa')
                        <i class="bi bi-patch-check-fill is-ico-green"></i>
                        <span>Final Approval</span>
                    @else
                        <i class="bi bi-lightning-charge-fill is-ico-gold"></i>
                        <span>Activities</span>
                    @endif
                    <em class="org-badge-count">12</em>
                </a>

                <a href="{{ route('office.calendar') }}" class="org-nav-link {{ ($activeNav ?? '') === 'calendar' ? 'is-active' : '' }}">
                    <i class="bi bi-calendar3 is-ico-blue"></i>
                    <span>Calendar</span>
                    <em class="org-badge-count">3</em>
                </a>

                {{-- Budget Utilization: removed on OVCAA DESK ONLY --}}
                @if (($office->office_role ?? '') !== 'ovcaa')
                <a href="{{ route('office.budget') }}" class="org-nav-link {{ ($activeNav ?? '') === 'budget' ? 'is-active' : '' }}">
                    <i class="bi bi-wallet2 is-ico-green"></i>
                    <span>Budget Utilization</span>
                </a>
                @endif

                {{-- Financial Report: removed on OVCAA and SDO --}}
                @if (!in_array(($office->office_role ?? ''), ['ovcaa', 'sdo']))
                <a href="{{ route('office.financial') }}" class="org-nav-link {{ ($activeNav ?? '') === 'financial' ? 'is-active' : '' }}">
                    <i class="bi bi-file-earmark-bar-graph is-ico-teal"></i>
                    <span>Financial Report</span>
                    <em class="org-badge-count">{{ $navBadges['fr_attachments'] ?? 4 }}</em>
                </a>
                @endif

                {{-- Accomplishment Report: removed on OVCAA and SDO --}}
                @if (!in_array(($office->office_role ?? ''), ['ovcaa', 'sdo']))
                <a href="{{ route('office.accomplishment') }}" class="org-nav-link {{ ($activeNav ?? '') === 'accomplishment' ? 'is-active' : '' }}">
                    <i class="bi bi-trophy is-ico-gold"></i>
                    <span>Accomplishment Report</span>
                    <em class="org-badge-count">{{ $navBadges['ar_attachments'] ?? 3 }}</em>
                </a>
                @endif

                <a href="{{ route('office.updates') }}" class="org-nav-link {{ ($activeNav ?? '') === 'updates' ? 'is-active' : '' }}">
                    <i class="bi bi-megaphone-fill is-ico-red"></i>
                    <span>Updates</span>
                    <em class="org-badge-count">3</em>
                </a>
                @if (($office->office_role ?? '') === 'oso')
                    <a href="{{ route('office.archive') }}" class="org-nav-link {{ ($activeNav ?? '') === 'archive' ? 'is-active' : '' }}">
                        <i class="bi bi-archive-fill is-ico-slate"></i>
                        <span>Archive</span>
                    </a>
                    <a href="{{ route('office.tosa') }}" class="org-nav-link {{ ($activeNav ?? '') === 'tosa' ? 'is-active' : '' }}">
                        <i class="bi bi-award-fill is-ico-maroon"></i>
                        <span>TOSA Module</span>
                        <em class="org-badge-count" style="background: rgba(139, 24, 40, 0.12); color: #8b1828; border: 1px solid #f2dfe2;"><i class="bi bi-lock-fill" style="font-size: 0.65rem;"></i></em>
                    </a>
                @endif
            </nav>

            @if (in_array(($office->office_role ?? ''), ['oso', 'ovcaa']))
            <div style="margin-top: auto; padding-top: 0.85rem; border-top: 1px solid var(--org-line); display: flex; flex-direction: column; gap: 0.35rem;">
                <button type="button" class="org-nav-link org-sidebar-settings-btn" onclick="openSettingsModal()" style="width: 100%; border: none; background: transparent; cursor: pointer; text-align: left; display: flex; align-items: center; gap: 0.75rem; font-family: inherit;">
                    <i class="bi bi-gear-fill is-ico-slate"></i>
                    <span>Settings</span>
                </button>
            </div>
            @endif
        </aside>

        <div class="org-main">
            <header class="org-topbar">
                <button type="button" class="org-menu-toggle" data-org-menu aria-label="Open menu" aria-expanded="false" aria-controls="orgSidebar">
                    <i class="bi bi-list"></i>
                </button>
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

                    {{-- User Profile Pill Menu (Right side of Bell) --}}
                    <div class="org-user-menu-wrap">
                        <button type="button" class="org-user-pill" id="orgUserMenuBtn" aria-expanded="false" aria-haspopup="true" aria-label="User menu" onclick="toggleOrgUserDropdown(event)">
                            <div class="org-user-avatar">
                                <span>{{ $office->initials() }}</span>
                            </div>
                            <div class="org-user-pill-info">
                                <span class="org-user-pill-name">{{ $office->name }}</span>
                                <span class="org-user-pill-role">{{ $brand['role'] }}</span>
                            </div>
                            <i class="bi bi-chevron-down org-user-chevron"></i>
                        </button>

                        <div class="org-user-dropdown liquid-glass" id="orgUserDropdown">
                            <div class="org-dropdown-header">
                                <div class="org-dropdown-avatar">
                                    <span>{{ $office->initials() }}</span>
                                </div>
                                <div class="org-dropdown-user-meta">
                                    <strong class="org-dropdown-name">{{ $office->name }}</strong>
                                    <span class="org-dropdown-role">{{ $brand['role'] }}</span>
                                    <small class="org-dropdown-email">{{ $office->email }}</small>
                                </div>
                            </div>

                            <div class="org-dropdown-divider"></div>

                            <button type="button" class="org-dropdown-item org-dropdown-settings-btn" onclick="openSettingsModal()">
                                <i class="bi bi-gear-wide-connected" style="color: var(--org-red);"></i>
                                <span>Settings</span>
                            </button>

                            <button type="button" class="org-dropdown-item org-dropdown-logout-btn" onclick="openLogoutModal()">
                                <i class="bi bi-box-arrow-left"></i>
                                <span>Logout</span>
                            </button>
                        </div>
                    </div>
                </div>
            </header>

            <div class="org-content">
                @yield('content')
            </div>
        </div>
    </div>

    <div class="org-page-loader" id="orgPageLoader" aria-hidden="true"><span></span></div>
    <div class="org-sidebar-overlay" id="orgSidebarOverlay" hidden></div>

    {{-- Executive Settings Hub Component (Front-End Only) --}}
    @include('org.settings-modal')

    {{-- Hidden Logout Form --}}
    <form method="post" action="{{ route('office.logout') }}" id="orgLogoutForm" style="display: none;">
        @csrf
    </form>

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
        function toggleOrgUserDropdown(e) {
            e.stopPropagation();
            const btn = document.getElementById('orgUserMenuBtn');
            const dropdown = document.getElementById('orgUserDropdown');
            if (!dropdown || !btn) return;
            
            const isOpen = dropdown.classList.contains('is-open');
            if (isOpen) {
                closeOrgUserDropdown();
            } else {
                dropdown.classList.add('is-open');
                btn.setAttribute('aria-expanded', 'true');
            }
        }

        function closeOrgUserDropdown() {
            const btn = document.getElementById('orgUserMenuBtn');
            const dropdown = document.getElementById('orgUserDropdown');
            if (dropdown) dropdown.classList.remove('is-open');
            if (btn) btn.setAttribute('aria-expanded', 'false');
        }

        document.addEventListener('click', function(e) {
            const menuWrap = document.querySelector('.org-user-menu-wrap');
            if (menuWrap && !menuWrap.contains(e.target)) {
                closeOrgUserDropdown();
            }
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeOrgUserDropdown();
                closeLogoutModal();
            }
        });

        function openLogoutModal() {
            closeOrgUserDropdown();
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

        /* No-reload sidebar navigation: swap topbar + content via fetch. */
        (function () {
            const executedScripts = new Set();
            document.querySelectorAll('script:not([src])').forEach((s) => {
                if (s.textContent.trim()) executedScripts.add(s.textContent);
            });

            const loader = () => document.getElementById('orgPageLoader');
            const sidebar = () => document.getElementById('orgSidebar');
            const overlay = () => document.getElementById('orgSidebarOverlay');

            window.closeOrgSidebar = function () {
                sidebar()?.classList.remove('is-open');
                overlay()?.setAttribute('hidden', '');
                document.querySelector('[data-org-menu]')?.setAttribute('aria-expanded', 'false');
                document.body.classList.remove('org-no-scroll');
            };

            const openOrgSidebar = () => {
                sidebar()?.classList.add('is-open');
                overlay()?.removeAttribute('hidden');
                document.querySelector('[data-org-menu]')?.setAttribute('aria-expanded', 'true');
                document.body.classList.add('org-no-scroll');
            };

            const runScript = (el) => {
                if (el.src) {
                    if (!document.querySelector('script[src="' + el.src + '"]')) {
                        const ns = document.createElement('script');
                        ns.src = el.src;
                        if (el.defer) ns.defer = true;
                        document.body.appendChild(ns);
                    }
                    return;
                }
                const code = el.textContent || '';
                if (!code.trim() || executedScripts.has(code)) return;
                executedScripts.add(code);
                const ns = document.createElement('script');
                ns.textContent = code;
                document.body.appendChild(ns);
            };

            const markActive = (url) => {
                let path = '';
                try { path = new URL(url, location.origin).pathname; } catch (_) { return; }
                document.querySelectorAll('.org-nav a.org-nav-link[href]').forEach((a) => {
                    let ap = '';
                    try { ap = new URL(a.getAttribute('href'), location.origin).pathname; } catch (_) { return; }
                    a.classList.toggle('is-active', ap === path);
                });
            };

            window.orgNavigate = async function (url, push = true) {
                const bar = loader();
                if (bar) bar.classList.add('is-loading');
                try {
                    const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' });
                    const html = await res.text();
                    const doc = new DOMParser().parseFromString(html, 'text/html');
                    const newTopbar = doc.querySelector('.org-topbar');
                    const newContent = doc.querySelector('.org-content');
                    if (!newTopbar || !newContent) { location.href = url; return; }
                    document.querySelector('.org-topbar')?.replaceWith(newTopbar);
                    document.querySelector('.org-content')?.replaceWith(newContent);
                    if (doc.title) document.title = doc.title;
                    doc.querySelectorAll('body script').forEach(runScript);
                    markActive(url);
                    if (push) history.pushState({ orgNav: true }, '', url);
                    window.closeOrgSidebar();
                    document.querySelector('.org-main')?.scrollTo({ top: 0 });
                    window.scrollTo({ top: 0 });
                } catch (_) {
                    location.href = url;
                } finally {
                    if (bar) bar.classList.remove('is-loading');
                }
            };

            document.addEventListener('click', (e) => {
                const menuBtn = e.target.closest('[data-org-menu]');
                if (menuBtn) {
                    if (sidebar()?.classList.contains('is-open')) window.closeOrgSidebar();
                    else openOrgSidebar();
                    return;
                }
                if (e.target.closest('#orgSidebarOverlay')) {
                    window.closeOrgSidebar();
                    return;
                }
                const link = e.target.closest('.org-nav a.org-nav-link[href]');
                if (link && !e.metaKey && !e.ctrlKey && !e.shiftKey && e.button === 0) {
                    e.preventDefault();
                    window.orgNavigate(link.getAttribute('href'), true);
                }
            });

            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && sidebar()?.classList.contains('is-open')) window.closeOrgSidebar();
            });

            /* If the drawer is open and the viewport grows back to desktop
               (e.g. window drag/resize), close it so no scroll-lock or
               overlay state leaks into the desktop layout. */
            const orgDesktopQuery = window.matchMedia('(min-width: 901px)');
            const syncOrgSidebarToViewport = (e) => {
                if (e.matches) window.closeOrgSidebar();
            };
            if (typeof orgDesktopQuery.addEventListener === 'function') {
                orgDesktopQuery.addEventListener('change', syncOrgSidebarToViewport);
            } else if (typeof orgDesktopQuery.addListener === 'function') {
                orgDesktopQuery.addListener(syncOrgSidebarToViewport);
            }

            window.addEventListener('popstate', () => {
                window.orgNavigate(location.href, false);
            });
        })();
    </script>
    @stack('scripts')
</body>
</html>
