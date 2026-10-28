<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Student Portal | OrgChain</title>
    <link rel="icon" type="image/png" href="{{ asset('Orgchain logo.png') }}">

    @fonts

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet">
        <link rel="stylesheet" href="{{ asset('css/landing.css') }}?v={{ filemtime(public_path('css/landing.css')) }}">
    @endif
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <script src="{{ asset('js/action-confirmations.js') }}?v={{ filemtime(public_path('js/action-confirmations.js')) }}"></script>
    <link rel="stylesheet" href="{{ asset('css/view-actions.css') }}?v={{ filemtime(public_path('css/view-actions.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/student-mobile.css') }}?v={{ filemtime(public_path('css/student-mobile.css')) }}">
    <style>.sp-sidebar .sp-nav-link span { font-weight:800; } .sp-sidebar .sp-brand-text span { white-space:normal; line-height:1.4; }</style>
    <script src="{{ asset('js/sdg-highlights.js') }}?v={{ filemtime(public_path('js/sdg-highlights.js')) }}" defer></script>
</head>
<body class="landing-body portal-body" data-portal-tab="{{ $tab ?? 'home' }}">
    <div class="page-ambient" aria-hidden="true">
        <span class="blob blob-a"></span>
        <span class="blob blob-b"></span>
        <span class="blob blob-c"></span>
    </div>

    <div class="sp-shell">
        {{-- Left sidebar --}}
        <aside class="sp-sidebar liquid-glass" id="spSidebar">
            <div class="sp-brand">
                <img src="{{ asset('Orgchain logo.png') }}" alt="OrgChain Logo" class="sp-brand-logo">
                <div class="sp-brand-text">
                    <strong>OrgChain</strong>
                    <span>BatStateU Student Portal</span>
                </div>
                <button type="button" class="sp-sidebar-close" id="spSidebarClose" aria-label="Close menu">
                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                </button>
            </div>

            <div class="sp-profile">
                <div class="sp-avatar" aria-hidden="true">
                    @if (!empty($student->avatar_path))
                        <img src="{{ asset('storage/'.$student->avatar_path) }}" alt="">
                    @else
                        <span>{{ $student->initials() }}</span>
                    @endif
                </div>
                <div class="sp-profile-info">
                    <strong>{{ $student->name }}</strong>
                    <span>{{ $student->sr_code }}</span>
                    @if (!empty($student->year_level))
                        <em>{{ str_ends_with(strtolower(trim($student->year_level)), 'year') ? trim($student->year_level) : trim($student->year_level) . ' Year' }}</em>
                    @endif
                </div>
            </div>

            @if (!empty($student->college))
                <div class="sp-profile-meta">
                    <p><i class="bi bi-building"></i> {{ $student->displayCollege() }}</p>
                </div>
            @endif

            <p class="sp-nav-section">Menu</p>
            <nav class="sp-nav" aria-label="Student Portal">
                <button type="button" class="sp-nav-link {{ ($tab ?? '') === 'home' ? 'is-active' : '' }}" data-portal-tab="home">
                    <i class="bi bi-grid-1x2-fill"></i>
                    <span>Dashboard</span>
                </button>
                <button type="button" class="sp-nav-link {{ ($tab ?? '') === 'activities' ? 'is-active' : '' }}" data-portal-tab="activities">
                    <i class="bi bi-calendar-event-fill"></i>
                    <span>Activities</span>
                </button>
                <button type="button" class="sp-nav-link {{ ($tab ?? '') === 'announcements' ? 'is-active' : '' }}" data-portal-tab="announcements">
                    <i class="bi bi-megaphone-fill"></i>
                    <span>Announcements</span>
                </button>
                <button type="button" class="sp-nav-link {{ ($tab ?? '') === 'tosa' ? 'is-active' : '' }}" data-portal-tab="tosa">
                    <i class="bi bi-award-fill"></i>
                    <span>TOSA Applications</span>
                </button>
                <button type="button" class="sp-nav-link {{ ($tab ?? '') === 'community' ? 'is-active' : '' }}" data-portal-tab="community">
                    <i class="bi bi-people-fill"></i>
                    <span>Community</span>
                </button>
            </nav>
        </aside>

        {{-- Main area --}}
        <div class="sp-main">
            <header class="sp-topbar">
                <button type="button" class="sp-menu-toggle" id="spMenuToggle" aria-label="Open menu" aria-expanded="false" aria-controls="spSidebar">
                    <i class="bi bi-list"></i>
                </button>
                <div class="sp-topbar-title">
                    <h1 class="sp-page-title" id="spPageTitle">{{ ($tab ?? 'home') === 'community' ? 'Community' : (($tab ?? 'home') === 'tosa' ? 'TOSA Applications' : (($tab ?? 'home') === 'announcements' ? 'Announcements' : (($tab ?? 'home') === 'activities' ? 'Activities' : 'Dashboard'))) }}</h1>
                    <p class="sp-module-kicker">BatStateU Student Portal</p>
                </div>
                <div class="sp-top-actions">
                    <form method="get" action="{{ route('portal.community') }}" class="sp-search-box" id="spTopSearchBox" @if(($tab ?? '') !== 'community') style="display: none;" @endif role="search">
                        <i class="bi bi-search" aria-hidden="true"></i>
                        <input type="search" name="community_q" value="{{ $communitySearch ?? '' }}" placeholder="Search community posts…" aria-label="Search community posts">
                        <input type="hidden" name="sort" value="{{ $feedSort ?? 'recent' }}">
                        <input type="hidden" name="community_audience" value="{{ $communityAudience ?? 'all' }}">
                        <input type="hidden" name="community_period" value="{{ $communityPeriod ?? 'all' }}">
                        <button type="submit" class="sp-link-btn">Search</button>
                    </form>
                    <div class="sp-top-menu-wrap">
                        <button type="button" class="sp-settings-btn" id="spSettingsBtn" aria-label="Settings" title="Settings" aria-expanded="false" aria-haspopup="true">
                            <i class="bi bi-gear"></i>
                        </button>
                        <div class="sp-user-dropdown sp-top-dropdown liquid-glass" id="spSettingsDropdown">
                            <div class="sp-dropdown-header">
                                <strong>Settings</strong>
                                <small>Customize your portal</small>
                            </div>
                            <div class="sp-dropdown-divider"></div>
                            <button type="button" class="sp-dropdown-item" id="spProfileSettingsBtn">
                                <i class="bi bi-person-gear"></i>
                                <span>Profile settings</span>
                            </button>
                        </div>
                    </div>
                    <div class="sp-top-menu-wrap">
                        <button type="button" class="sp-bell-btn" id="spBellBtn" aria-label="Notifications" title="Notifications" aria-expanded="false" aria-haspopup="true">
                            <i class="bi bi-bell"></i>
                            <span class="sp-bell-dot" id="spBellDot"></span>
                        </button>
                        <div class="sp-user-dropdown sp-top-dropdown liquid-glass" id="spBellDropdown">
                            <div class="sp-dropdown-header sp-dropdown-header-row">
                                <div>
                                    <strong>Notifications</strong>
                                    <small>Latest from OSO</small>
                                </div>
                                <button type="button" class="sp-link-btn" id="spMarkReadBtn">Mark all read</button>
                            </div>
                            <div class="sp-dropdown-divider"></div>
                            <div class="sp-notif-list" id="spNotifList"></div>
                        </div>
                    </div>
                    <div class="sp-user-menu-wrap">
                        <button type="button" class="sp-user-pill" id="spUserMenuBtn" aria-expanded="false" aria-haspopup="true" aria-label="User menu">
                            <div class="sp-user-avatar">
                                @if (!empty($student->avatar_path))
                                    <img src="{{ asset('storage/'.$student->avatar_path) }}" alt="">
                                @else
                                    <span>{{ $student->initials() ?? 'MV' }}</span>
                                @endif
                            </div>
                            <span class="sp-user-name">{{ $student->name ?? 'Michelle Vivas' }}</span>
                            <i class="bi bi-chevron-down"></i>
                        </button>
                        <div class="sp-user-dropdown liquid-glass" id="spUserDropdown">
                            <div class="sp-dropdown-header">
                                <strong>{{ $student->name ?? 'Student' }}</strong>
                                <small>{{ $student->sr_code ?? '' }}</small>
                            </div>
                            <div class="sp-dropdown-divider"></div>
                            <form method="post" action="{{ route('student.logout') }}" class="sp-dropdown-logout" data-confirm-message="Log out of your student portal?">
                                @csrf
                                <button type="submit" class="sp-dropdown-item">
                                    <i class="bi bi-box-arrow-left"></i>
                                    <span>Logout</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            <div class="sp-content" id="portalMain">
                @if (session('status'))
                    <div class="sp-flash" role="status" aria-live="polite" data-success-flash>
                        <i class="bi bi-check-circle-fill"></i>
                        <span>{{ session('status') }}</span>
                    </div>
                @endif

                @if ($errors->any() && ($tab ?? '') !== 'tosa')
                    <div class="sp-error" role="alert">
                        <strong>Please correct the following:</strong>
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                @yield('content')
            </div>
        </div>
    </div>

    {{-- Mobile sidebar overlay --}}
    <div class="sp-overlay" id="spOverlay" hidden></div>

    {{-- Likers modal --}}
    <div class="sp-modal-backdrop" id="spLikersModal" aria-hidden="true">
        <div class="sp-modal-box liquid-glass" role="dialog" aria-modal="true" aria-labelledby="spLikersTitle">
            <div class="sp-modal-head">
                <div class="sp-modal-head-title"><i class="bi bi-hand-thumbs-up-fill"></i> <span id="spLikersTitle">Likes</span></div>
                <button type="button" class="sp-modal-close-btn" data-close-likers aria-label="Close"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="sp-likers-list" id="spLikersList"></div>
        </div>
    </div>

    {{-- Profile settings modal --}}
    <div class="sp-modal-backdrop" id="spProfileModal" aria-hidden="true">
        <div class="sp-modal-box liquid-glass" role="dialog" aria-modal="true" aria-labelledby="spProfileModalTitle">
            <div class="sp-modal-head">
                <div class="sp-modal-head-title"><i class="bi bi-person-gear"></i> <span id="spProfileModalTitle">Profile settings</span></div>
                <button type="button" class="sp-modal-close-btn" data-close-profile aria-label="Close"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="sp-profile-form">
                <p class="sp-profile-readonly-note">Official student record. Contact the registrar for corrections.</p>
                <label>
                    <span>Full name</span>
                    <input type="text" value="{{ $student->name }}" readonly aria-readonly="true">
                </label>
                <label>
                    <span>College / Department</span>
                    <input type="text" value="{{ $student->college }}" readonly aria-readonly="true">
                </label>
                <label>
                    <span>Year level</span>
                    <input type="text" value="{{ $student->year_level }}" readonly aria-readonly="true">
                </label>
                <div class="sp-profile-form-actions">
                    <button type="button" class="sp-btn-ghost" data-close-profile>Close</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const urls = {
                home: @json(route('portal.home')),
                activities: @json(route('portal.home')) + '#activities',
                announcements: @json(route('portal.home')) + '#announcements',
                tosa: @json(route('portal.home')) + '#tosa',
                community: @json(route('portal.community')),
            };
            const titles = {
                home: 'Dashboard',
                activities: 'Activities',
                announcements: 'Announcements',
                tosa: 'TOSA Applications',
                community: 'Community'
            };

            const pageTitleEl = document.getElementById('spPageTitle');
            const sidebar = document.getElementById('spSidebar');
            const overlay = document.getElementById('spOverlay');
            const menuToggle = document.getElementById('spMenuToggle');
            const sidebarClose = document.getElementById('spSidebarClose');
            const spDesktopQuery = window.matchMedia('(min-width: 993px)');

            const closeSidebar = (restoreFocus = false) => {
                if (!sidebar) return;
                const wasOpen = sidebar.classList.contains('is-open');
                if (wasOpen && !spDesktopQuery.matches && (restoreFocus || sidebar.contains(document.activeElement))) {
                    menuToggle?.focus({ preventScroll: true });
                }
                sidebar.classList.remove('is-open');
                if (overlay) overlay.hidden = true;
                if (menuToggle) {
                    menuToggle.setAttribute('aria-expanded', 'false');
                    menuToggle.setAttribute('aria-label', 'Open menu');
                }
                sidebar.toggleAttribute('inert', !spDesktopQuery.matches);
                if (spDesktopQuery.matches) sidebar.removeAttribute('aria-hidden');
                else sidebar.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('sp-no-scroll');
            };

            const openSidebar = () => {
                if (!sidebar || spDesktopQuery.matches) return;
                sidebar.removeAttribute('inert');
                sidebar.removeAttribute('aria-hidden');
                sidebar.classList.add('is-open');
                if (overlay) overlay.hidden = false;
                if (menuToggle) {
                    menuToggle.setAttribute('aria-expanded', 'true');
                    menuToggle.setAttribute('aria-label', 'Close menu');
                }
                document.body.classList.add('sp-no-scroll');
                sidebarClose?.focus({ preventScroll: true });
            };

            menuToggle?.addEventListener('click', () => {
                if (sidebar?.classList.contains('is-open')) closeSidebar(true);
                else openSidebar();
            });
            sidebarClose?.addEventListener('click', () => closeSidebar(true));
            overlay?.addEventListener('click', () => closeSidebar(true));

            document.addEventListener('keydown', (event) => {
                if (event.key !== 'Tab' || !sidebar?.classList.contains('is-open')) return;
                const focusable = Array.from(sidebar.querySelectorAll('button:not([disabled]), a[href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'))
                    .filter((element) => element.getClientRects().length > 0);
                if (!focusable.length) return;
                const first = focusable[0];
                const last = focusable[focusable.length - 1];
                if (event.shiftKey && document.activeElement === first) {
                    event.preventDefault();
                    last.focus();
                } else if (!event.shiftKey && document.activeElement === last) {
                    event.preventDefault();
                    first.focus();
                }
            });

            const syncSpSidebarToViewport = () => closeSidebar();
            if (typeof spDesktopQuery.addEventListener === 'function') {
                spDesktopQuery.addEventListener('change', syncSpSidebarToViewport);
            } else if (typeof spDesktopQuery.addListener === 'function') {
                spDesktopQuery.addListener(syncSpSidebarToViewport);
            }
            syncSpSidebarToViewport();

            const switchTab = (tab, push = true) => {
                if (!['home', 'community', 'activities', 'announcements', 'tosa'].includes(tab)) return;

                document.body.dataset.portalTab = tab;

                document.querySelectorAll('.portal-panel').forEach((panel) => {
                    const active = panel.dataset.panel === tab;
                    panel.hidden = !active;
                    panel.classList.toggle('is-active', active);
                });

                document.querySelectorAll('.sp-nav-link[data-portal-tab]').forEach((btn) => {
                    const active = btn.dataset.portalTab === tab;
                    btn.classList.toggle('is-active', active);
                });

                if (pageTitleEl) {
                    pageTitleEl.textContent = titles[tab] || 'Dashboard';
                }

                const topSearchBox = document.getElementById('spTopSearchBox');
                if (topSearchBox) {
                    topSearchBox.style.display = tab === 'community' ? '' : 'none';
                }

                document.getElementById('portalMain')?.scrollTo({ top: 0, behavior: 'smooth' });
                window.scrollTo({ top: 0, behavior: 'smooth' });

                if (push && urls[tab]) {
                    history.pushState({ tab }, '', urls[tab]);
                }

                document.title = (titles[tab] || 'Dashboard') + ' | OrgChain';

                closeSidebar();
            };

            document.querySelectorAll('[data-portal-tab]').forEach((el) => {
                el.addEventListener('click', (e) => {
                    const tab = el.getAttribute('data-portal-tab');
                    if (!tab) return;
                    if (el.tagName === 'BUTTON' || el.classList.contains('sp-nav-link')) {
                        e.preventDefault();
                        switchTab(tab, true);
                    }
                });
            });

            window.addEventListener('popstate', (e) => {
                const tab = e.state?.tab || (location.hash === '#tosa' ? 'tosa' : (location.hash === '#announcements' ? 'announcements' : (location.hash === '#activities' ? 'activities' : (location.pathname.includes('/community') ? 'community' : 'home'))));
                switchTab(tab, false);
            });

            const initialHashTab = location.hash.slice(1);
            if (['activities', 'announcements', 'tosa'].includes(initialHashTab)) {
                switchTab(initialHashTab, false);
            }

            // -------------------------------------------------------------
            // Announcements filtering and pagination
            // -------------------------------------------------------------
            (function initAnnouncementsEngine() {
                const PAGE_SIZE = 5;
                let currentPage = 1;
                let activeCat = 'all';
                let activePriority = 'all';
                let activeSort = 'newest';
                let searchQuery = '';

                const searchInput = document.getElementById('spAnnounceSearchInput');
                const prioritySelect = document.getElementById('spAnnouncePriorityFilter');
                const sortSelect = document.getElementById('spAnnounceSortSelect');
                const pageNumbersContainer = document.getElementById('spAnnouncePageNumbers');
                const prevBtn = document.getElementById('spAnnouncePrevBtn');
                const nextBtn = document.getElementById('spAnnounceNextBtn');
                const pageRangeEl = document.getElementById('spAnnouncePageRange');
                const totalItemsEl = document.getElementById('spAnnounceTotalItems');
                const countBadgeEl = document.getElementById('spAnnounceCountBadge');
                const emptyRow = document.getElementById('spAnnounceEmptyRow');

                const getAllRows = () => Array.from(document.querySelectorAll('#spAnnouncementsTbody tr.sp-announce-tbl-row'));

                const renderTable = () => {
                    const allRows = getAllRows();

                    // 1. Filter rows
                    const filtered = allRows.filter((row) => {
                        const cat = row.dataset.announceCat || '';
                        const prio = row.dataset.announcePriority || '';
                        const search = row.dataset.announceSearch || '';

                        const matchCat = activeCat === 'all' || cat === activeCat;
                        const matchPrio = activePriority === 'all' || prio === activePriority;
                        const matchSearch = !searchQuery || search.includes(searchQuery.toLowerCase());

                        return matchCat && matchPrio && matchSearch;
                    });

                    // 2. Sort rows
                    filtered.sort((a, b) => {
                        const orderA = parseInt(a.dataset.announceOrder || '0', 10);
                        const orderB = parseInt(b.dataset.announceOrder || '0', 10);
                        return activeSort === 'oldest' ? orderA - orderB : orderB - orderA;
                    });

                    const totalItems = filtered.length;
                    const totalPages = Math.ceil(totalItems / PAGE_SIZE) || 1;
                    if (currentPage > totalPages) currentPage = totalPages;
                    if (currentPage < 1) currentPage = 1;

                    // 3. Hide all rows first
                    allRows.forEach((r) => (r.style.display = 'none'));

                    // 4. Show current page slice
                    const startIndex = (currentPage - 1) * PAGE_SIZE;
                    const endIndex = Math.min(startIndex + PAGE_SIZE, totalItems);
                    const pageRows = filtered.slice(startIndex, endIndex);

                    pageRows.forEach((r) => (r.style.display = ''));

                    // 5. Empty state handling
                    if (emptyRow) {
                        emptyRow.style.display = totalItems === 0 ? '' : 'none';
                    }

                    // 6. Summary Info
                    const startDisplay = totalItems === 0 ? 0 : startIndex + 1;
                    if (pageRangeEl) pageRangeEl.textContent = `${startDisplay}–${endIndex}`;
                    if (totalItemsEl) totalItemsEl.textContent = totalItems;
                    if (countBadgeEl) {
                        countBadgeEl.textContent = `Showing ${startDisplay}–${endIndex} of ${totalItems} bulletins`;
                    }

                    if (pageNumbersContainer) {
                        pageNumbersContainer.textContent = `Page ${currentPage} of ${totalPages}`;
                        pageNumbersContainer.setAttribute('aria-live', 'polite');
                    }

                    // 8. Prev / Next button state
                    if (prevBtn) {
                        prevBtn.disabled = currentPage <= 1;
                        prevBtn.onclick = () => {
                            if (currentPage > 1) {
                                currentPage--;
                                renderTable();
                            }
                        };
                    }
                    if (nextBtn) {
                        nextBtn.disabled = currentPage >= totalPages;
                        nextBtn.onclick = () => {
                            if (currentPage < totalPages) {
                                currentPage++;
                                renderTable();
                            }
                        };
                    }
                };

                // Category Tab Listeners
                document.querySelectorAll('#spAnnounceTabs .sp-announcements-tab-btn').forEach((btn) => {
                    btn.addEventListener('click', () => {
                        document.querySelectorAll('#spAnnounceTabs .sp-announcements-tab-btn').forEach((b) => b.classList.remove('is-active'));
                        btn.classList.add('is-active');
                        activeCat = btn.dataset.announceTab || 'all';
                        currentPage = 1;
                        renderTable();
                    });
                });

                // Priority Filter Listener
                prioritySelect?.addEventListener('change', () => {
                    activePriority = prioritySelect.value;
                    currentPage = 1;
                    renderTable();
                });

                // Sort Order Listener
                sortSelect?.addEventListener('change', () => {
                    activeSort = sortSelect.value;
                    renderTable();
                });

                // Live Search Input
                searchInput?.addEventListener('input', () => {
                    searchQuery = (searchInput.value || '').trim();
                    currentPage = 1;
                    renderTable();
                });

                // Initial render
                renderTable();
            })();

            // -------------------------------------------------------------
            // Modern Post Composer Bottom Toolbar Handlers (Picture 1)
            // -------------------------------------------------------------
            const chipsContainer = document.getElementById('spComposerChips');
            const updateChipsVisibility = () => {
                if (!chipsContainer) return;
                const anyVisible = Array.from(chipsContainer.querySelectorAll('.sp-composer-chip')).some((c) => !c.hidden);
                chipsContainer.hidden = !anyVisible;
            };

            // 1. Photo / Video Attachment
            const photoInput = document.getElementById('communityPhotoInput');
            const chipPhoto = document.getElementById('spChipPhoto');
            const chipPhotoName = document.getElementById('spChipPhotoName');
            const removePhotoBtn = document.getElementById('spRemovePhotoBtn');

            photoInput?.addEventListener('change', function () {
                if (this.files && this.files[0]) {
                    if (chipPhotoName) chipPhotoName.textContent = this.files[0].name;
                    if (chipPhoto) chipPhoto.hidden = false;
                    updateChipsVisibility();
                }
            });

            removePhotoBtn?.addEventListener('click', (e) => {
                e.preventDefault();
                if (photoInput) photoInput.value = '';
                if (chipPhoto) chipPhoto.hidden = true;
                updateChipsVisibility();
            });

            // Generic Popover Closer Helper
            const closeAllComposerPopovers = () => {
                document.querySelectorAll('.sp-action-popover').forEach((p) => (p.hidden = true));
            };

            // 3. Activity / Event Link
            const actBtn = document.getElementById('spComposerActivityBtn');
            const actPopover = document.getElementById('spActivityPopover');
            const actClose = document.getElementById('spActivityPopoverClose');
            const actSelect = document.getElementById('spComposerActivitySelect');
            const chipActivity = document.getElementById('spChipActivity');
            const chipActivityName = document.getElementById('spChipActivityName');
            const removeActivityBtn = document.getElementById('spRemoveActivityBtn');

            actBtn?.addEventListener('click', (e) => {
                e.stopPropagation();
                const willOpen = actPopover?.hidden;
                closeAllComposerPopovers();
                if (actPopover) actPopover.hidden = !willOpen;
            });

            actClose?.addEventListener('click', (e) => {
                e.stopPropagation();
                if (actPopover) actPopover.hidden = true;
            });

            document.querySelectorAll('.sp-act-option').forEach((opt) => {
                opt.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const actId = opt.dataset.actId || '';
                    const actTitle = opt.dataset.actTitle || opt.textContent.trim();

                    document.querySelectorAll('.sp-act-option').forEach((o) => o.classList.remove('is-active'));
                    opt.classList.add('is-active');

                    if (actSelect) actSelect.value = actId;

                    if (actId) {
                        if (chipActivityName) chipActivityName.textContent = actTitle;
                        if (chipActivity) chipActivity.hidden = false;
                    } else {
                        if (chipActivity) chipActivity.hidden = true;
                    }
                    if (actPopover) actPopover.hidden = true;
                    updateChipsVisibility();
                });
            });

            removeActivityBtn?.addEventListener('click', (e) => {
                e.preventDefault();
                if (actSelect) actSelect.value = '';
                document.querySelectorAll('.sp-act-option').forEach((o) => {
                    o.classList.toggle('is-active', (o.dataset.actId || '') === '');
                });
                if (chipActivity) chipActivity.hidden = true;
                updateChipsVisibility();
            });

            // 4. Feeling / Activity
            const feelingBtn = document.getElementById('spComposerFeelingBtn');
            const feelingPopover = document.getElementById('spFeelingPopover');
            const feelingClose = document.getElementById('spFeelingPopoverClose');
            const feelingInput = document.getElementById('spComposerFeelingInput');
            const chipFeeling = document.getElementById('spChipFeeling');
            const chipFeelingName = document.getElementById('spChipFeelingName');
            const removeFeelingBtn = document.getElementById('spRemoveFeelingBtn');

            feelingBtn?.addEventListener('click', (e) => {
                e.stopPropagation();
                const willOpen = feelingPopover?.hidden;
                closeAllComposerPopovers();
                if (feelingPopover) feelingPopover.hidden = !willOpen;
            });

            feelingClose?.addEventListener('click', (e) => {
                e.stopPropagation();
                if (feelingPopover) feelingPopover.hidden = true;
            });

            document.querySelectorAll('.sp-feeling-btn').forEach((btn) => {
                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const feeling = btn.dataset.feeling || btn.textContent.trim();
                    if (feelingInput) feelingInput.value = feeling;
                    if (chipFeelingName) chipFeelingName.textContent = 'Feeling ' + feeling;
                    if (chipFeeling) chipFeeling.hidden = false;
                    if (feelingPopover) feelingPopover.hidden = true;
                    updateChipsVisibility();
                });
            });

            removeFeelingBtn?.addEventListener('click', (e) => {
                e.preventDefault();
                if (feelingInput) feelingInput.value = '';
                if (chipFeeling) chipFeeling.hidden = true;
                updateChipsVisibility();
            });

            // 5. Audience Selector (Public ▾)
            const audienceBtn = document.getElementById('spAudienceBtn');
            const audiencePopover = document.getElementById('spAudiencePopover');
            const audienceInput = document.getElementById('spComposerAudienceInput');
            const audienceLabel = document.getElementById('spAudienceLabel');
            const audienceIcon = document.getElementById('spAudienceIcon');

            audienceBtn?.addEventListener('click', (e) => {
                e.stopPropagation();
                const willOpen = audiencePopover?.hidden;
                closeAllComposerPopovers();
                if (audiencePopover) audiencePopover.hidden = !willOpen;
            });

            document.querySelectorAll('.sp-audience-item').forEach((item) => {
                item.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const aud = item.dataset.audience || 'public';
                    const iconClass = item.dataset.icon || 'bi-globe-americas';
                    const label = item.dataset.label || 'Public';

                    document.querySelectorAll('.sp-audience-item').forEach((i) => i.classList.remove('is-active'));
                    item.classList.add('is-active');

                    if (audienceInput) audienceInput.value = aud;
                    if (audienceLabel) audienceLabel.textContent = label;
                    if (audienceIcon) audienceIcon.className = `bi ${iconClass}`;
                    if (audiencePopover) audiencePopover.hidden = true;
                });
            });
            const initialAudience = Array.from(document.querySelectorAll('.sp-audience-item')).find((item) => item.dataset.audience === audienceInput?.value);
            if (initialAudience) {
                document.querySelectorAll('.sp-audience-item').forEach((item) => item.classList.toggle('is-active', item === initialAudience));
                if (audienceLabel) audienceLabel.textContent = initialAudience.dataset.label;
                if (audienceIcon) audienceIcon.className = `bi ${initialAudience.dataset.icon}`;
            }

            // Close composer popovers on click outside
            document.addEventListener('click', (e) => {
                if (!e.target.closest('.sp-action-dropdown-wrap')) {
                    closeAllComposerPopovers();
                }
                if (!e.target.closest('.sp-feed-sort-dropdown-wrap')) {
                    const sortPop = document.getElementById('spFeedSortPopover');
                    if (sortPop) sortPop.hidden = true;
                    document.getElementById('spFeedSortBtn')?.setAttribute('aria-expanded', 'false');
                }
                if (!e.target.closest('.sp-post-menu-wrap')) {
                    document.querySelectorAll('.sp-post-options-menu').forEach((m) => (m.hidden = true));
                }
                if (!e.target.closest('.sp-feed-filter-dropdown')) {
                    document.querySelector('.sp-feed-filter-dropdown')?.removeAttribute('open');
                }
            });

            // Feed Sort Dropdown Toggle
            const feedSortBtn = document.getElementById('spFeedSortBtn');
            const feedSortPopover = document.getElementById('spFeedSortPopover');

            feedSortBtn?.addEventListener('click', (e) => {
                e.stopPropagation();
                if (feedSortPopover) {
                    feedSortPopover.hidden = !feedSortPopover.hidden;
                    if (!feedSortPopover.hidden) {
                        document.querySelector('.sp-feed-filter-dropdown')?.removeAttribute('open');
                    }
                    feedSortBtn.setAttribute('aria-expanded', String(!feedSortPopover.hidden));
                }
            });

            document.addEventListener('keydown', (event) => {
                if (event.key !== 'Escape') return;
                if (feedSortPopover && !feedSortPopover.hidden) {
                    feedSortPopover.hidden = true;
                    feedSortBtn?.setAttribute('aria-expanded', 'false');
                    feedSortBtn?.focus();
                }
                const filters = document.querySelector('.sp-feed-filter-dropdown[open]');
                if (filters) {
                    filters.removeAttribute('open');
                    filters.querySelector('summary')?.focus();
                }
            });

            document.querySelectorAll('[data-post-new-until]').forEach((badge) => {
                const remaining = Number(badge.dataset.postNewUntil) - Date.now();
                if (remaining <= 0) badge.remove();
                else window.setTimeout(() => badge.remove(), remaining);
            });
            document.addEventListener('visibilitychange', () => {
                if (document.hidden) return;
                document.querySelectorAll('[data-post-new-until]').forEach((badge) => {
                    if (Number(badge.dataset.postNewUntil) <= Date.now()) badge.remove();
                });
            });


            // Single source of truth for likes (one request per tap)
            const likeHeaders = () => ({
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            });

            const paintLikeState = (card, reaction, count) => {
                const likeBtn = card?.querySelector('.community-like-btn');
                const textEl = card?.querySelector('[data-likes-count] .sp-reaction-text');
                const selected = Array.from(card?.querySelectorAll('[data-reaction].sp-dock-reaction') || []).find((option) => option.dataset.reaction === reaction);
                if (likeBtn) {
                    likeBtn.classList.toggle('is-liked', Boolean(reaction));
                    likeBtn.dataset.reaction = reaction || '';
                    const labelEl = likeBtn.querySelector('.sp-like-label');
                    const iconEl = likeBtn.querySelector('i');
                    if (labelEl) labelEl.textContent = selected?.dataset.label || 'Like';
                    if (iconEl) iconEl.className = `bi ${selected?.dataset.icon || 'bi-hand-thumbs-up'} sp-action-love-icon`;
                }
                if (textEl) textEl.textContent = `${count} like${count === 1 ? '' : 's'}`;
            };

            const sendLike = async (card, reaction = null) => {
                const likeBtn = card?.querySelector('.community-like-btn');
                const url = likeBtn?.dataset.likeUrl;
                if (!url || !card || !likeBtn || likeBtn.disabled) return;
                likeBtn.disabled = true;
                try {
                    const payload = new FormData();
                    payload.set('reaction', reaction || likeBtn.dataset.reaction || 'like');
                    const res = await fetch(url, { method: 'POST', headers: likeHeaders(), body: payload, credentials: 'same-origin' });
                    if (!res.ok) throw new Error('like failed');
                    const data = await res.json();
                    paintLikeState(card, data.reaction || null, Number(data.likes_count ?? 0));
                } catch (_) {
                    /* keep current UI on failure */
                } finally {
                    likeBtn.disabled = false;
                }
            };

            // Hover, focus and long press expose supported server-persisted reactions.
            const closeReactionDocks = (except = null) => {
                document.querySelectorAll('.sp-reactions-dock').forEach((dock) => {
                    if (dock !== except) dock.hidden = true;
                });
            };
            document.querySelectorAll('.sp-like-action-wrap').forEach((wrap) => {
                const dock = wrap.querySelector('.sp-reactions-dock');
                if (!dock) return;
                let holdTimer;
                let hideTimer;
                const show = () => {
                    window.clearTimeout(hideTimer);
                    closeReactionDocks(dock);
                    dock.hidden = false;
                };
                wrap.addEventListener('mouseenter', show);
                wrap.addEventListener('mouseleave', () => { hideTimer = window.setTimeout(() => { dock.hidden = true; }, 250); });
                wrap.addEventListener('focusin', show);
                wrap.addEventListener('focusout', (event) => {
                    if (!wrap.contains(event.relatedTarget)) dock.hidden = true;
                });
                wrap.addEventListener('touchstart', () => { holdTimer = window.setTimeout(show, 300); }, { passive: true });
                wrap.addEventListener('touchend', () => window.clearTimeout(holdTimer));
                wrap.addEventListener('touchcancel', () => window.clearTimeout(holdTimer));
            });
            document.addEventListener('click', (e) => {
                const reaction = e.target.closest('.sp-dock-reaction');
                if (reaction) {
                    e.stopPropagation();
                    closeReactionDocks();
                    sendLike(reaction.closest('.community-post'), reaction.dataset.reaction);
                    return;
                }
                if (!e.target.closest('.sp-like-action-wrap')) closeReactionDocks();

                // Handle standard tap / click on Like Button (Toggle)
                const likeBtn = e.target.closest('.community-like-btn');
                if (likeBtn) {
                    e.stopPropagation();
                    sendLike(likeBtn.closest('.community-post'));
                }
            });


            // Post Options Menu Trigger
            document.addEventListener('click', (e) => {
                const trigger = e.target.closest('.sp-post-menu-trigger');
                if (trigger) {
                    e.stopPropagation();
                    const menu = trigger.parentElement?.querySelector('.sp-post-options-menu');
                    const isHidden = menu ? menu.hidden : true;
                    document.querySelectorAll('.sp-post-options-menu').forEach((m) => (m.hidden = true));
                    if (menu) menu.hidden = !isHidden;
                }
            });

            // User menu dropdown toggle
            const userMenuBtn = document.getElementById('spUserMenuBtn');
            const userDropdown = document.getElementById('spUserDropdown');

            if (userMenuBtn && userDropdown) {
                userMenuBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const isOpen = userDropdown.classList.toggle('is-open');
                    userMenuBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
                });

                document.addEventListener('click', (e) => {
                    if (!userDropdown.contains(e.target) && !userMenuBtn.contains(e.target)) {
                        userDropdown.classList.remove('is-open');
                        userMenuBtn.setAttribute('aria-expanded', 'false');
                    }
                });

                document.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape' && userDropdown.classList.contains('is-open')) {
                        userDropdown.classList.remove('is-open');
                        userMenuBtn.setAttribute('aria-expanded', 'false');
                        userMenuBtn.focus();
                    }
                });
            }

            // Top-bar Settings + Notifications dropdowns
            const topDropdowns = [
                { btn: document.getElementById('spSettingsBtn'), panel: document.getElementById('spSettingsDropdown') },
                { btn: document.getElementById('spBellBtn'), panel: document.getElementById('spBellDropdown') },
            ];

            const closeTopDropdowns = (except = null) => {
                topDropdowns.forEach(({ btn, panel }) => {
                    if (!panel || panel === except) return;
                    panel.classList.remove('is-open');
                    btn?.setAttribute('aria-expanded', 'false');
                });
            };

            topDropdowns.forEach(({ btn, panel }) => {
                btn?.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const willOpen = !panel?.classList.contains('is-open');
                    closeTopDropdowns();
                    userDropdown?.classList.remove('is-open');
                    if (willOpen && panel) {
                        panel.classList.add('is-open');
                        btn.setAttribute('aria-expanded', 'true');
                    } else {
                        btn?.setAttribute('aria-expanded', 'false');
                    }
                });
            });

            document.addEventListener('click', (e) => {
                if (!e.target.closest('.sp-top-menu-wrap')) closeTopDropdowns();
            });

            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') closeTopDropdowns();
            });

            // Notifications reflect persisted announcements and this student's acknowledgments.
            const notifList = document.getElementById('spNotifList');
            const bellDot = document.getElementById('spBellDot');
            const announcementsFeed = @json(($announcements ?? []));
            const feedbackFeed = @json(($feedbackNotifications ?? []));
            const notificationFeed = [
                ...feedbackFeed.map((item) => ({ ...item, feedback: true })),
                ...announcementsFeed,
            ];
            const notifReadKey = 'sp_notif_read_{{ $student->id }}';
            const notificationVersion = JSON.stringify(notificationFeed.map((item) => [item.id, item.acknowledgedAt || item.published_at || item.time]));
            if (notifList) {
                if (!notificationFeed.length) {
                    notifList.innerHTML = '<div class="sp-notif-empty"><i class="bi bi-bell-slash"></i><span>No notifications yet.</span></div>';
                }
                notificationFeed.forEach((notice) => {
                    const item = document.createElement('a');
                    item.className = 'sp-notif-item' + (notice.priority === 'high' ? ' is-high' : '');
                    item.href = notice.url || tabUrls.announcements;
                    item.innerHTML = '<span class="sp-notif-dot" aria-hidden="true"></span><span class="sp-notif-text"><strong></strong><small></small></span>';
                    item.querySelector('strong').textContent = notice.title || 'Announcement';
                    item.querySelector('small').textContent = `${notice.author || 'OSO'} · ${notice.time || ''}`;
                    if (notice.feedback) item.querySelector('small').textContent += ' · Acknowledged';
                    notifList.appendChild(item);
                });
            }
            const syncBellDot = () => {
                if (bellDot) bellDot.hidden = !notificationFeed.length || localStorage.getItem(notifReadKey) === notificationVersion;
            };
            document.getElementById('spMarkReadBtn')?.addEventListener('click', () => {
                localStorage.setItem(notifReadKey, notificationVersion);
                syncBellDot();
            });
            syncBellDot();
            document.querySelectorAll('[data-success-flash]').forEach((flash) => {
                window.setTimeout(() => flash.remove(), 4000);
            });

            // Profile settings modal
            const profileModal = document.getElementById('spProfileModal');
            const openProfile = () => {
                closeTopDropdowns();
                profileModal?.classList.add('is-open');
                profileModal?.setAttribute('aria-hidden', 'false');
            };
            const closeProfile = () => {
                profileModal?.classList.remove('is-open');
                profileModal?.setAttribute('aria-hidden', 'true');
            };
            document.getElementById('spProfileSettingsBtn')?.addEventListener('click', openProfile);
            profileModal?.querySelectorAll('[data-close-profile]').forEach((b) => b.addEventListener('click', closeProfile));
            profileModal?.addEventListener('click', (e) => { if (e.target === profileModal) closeProfile(); });

            // Likers modal
            const likersModal = document.getElementById('spLikersModal');
            const likersList = document.getElementById('spLikersList');
            const likersTitle = document.getElementById('spLikersTitle');
            const closeLikers = () => {
                likersModal?.classList.remove('is-open');
                likersModal?.setAttribute('aria-hidden', 'true');
            };
            likersModal?.querySelectorAll('[data-close-likers]').forEach((b) => b.addEventListener('click', closeLikers));
            likersModal?.addEventListener('click', (e) => { if (e.target === likersModal) closeLikers(); });
            document.addEventListener('keydown', (e) => { if (e.key === 'Escape') { closeLikers(); closeProfile(); } });

            document.addEventListener('click', async (e) => {
                const likersBtn = e.target.closest('.sp-likers-btn');
                if (!likersBtn || !likersModal || !likersList) return;
                e.stopPropagation();
                if (likersTitle) likersTitle.textContent = 'Likes';
                likersList.innerHTML = '<div class="sp-notif-empty"><i class="bi bi-arrow-repeat"></i><span>Loading likes…</span></div>';
                likersModal.classList.add('is-open');
                likersModal.setAttribute('aria-hidden', 'false');
                try {
                    const res = await fetch(likersBtn.dataset.likersUrl, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' });
                    const data = await res.json();
                    if (likersTitle) likersTitle.textContent = `${data.likes_count ?? 0} like${(data.likes_count ?? 0) === 1 ? '' : 's'}`;
                    likersList.innerHTML = '';
                    if (!data.likers?.length) {
                        likersList.innerHTML = '<div class="sp-notif-empty"><i class="bi bi-hand-thumbs-up"></i><span>No likes yet. Be the first!</span></div>';
                        return;
                    }
                    data.likers.forEach((person) => {
                        const row = document.createElement('div');
                        row.className = 'sp-liker-row';
                        const avatar = document.createElement('span');
                        avatar.className = 'sp-liker-avatar';
                        avatar.textContent = person.initials || '?';
                        const name = document.createElement('strong');
                        name.textContent = person.name || 'Student';
                        row.appendChild(avatar);
                        row.appendChild(name);
                        likersList.appendChild(row);
                    });
                } catch (_) {
                    likersList.innerHTML = '<div class="sp-notif-empty"><i class="bi bi-exclamation-circle"></i><span>Could not load likes.</span></div>';
                }
            });

            // View all / show less comments + scroll to comments
            document.addEventListener('click', (e) => {
                const toggle = e.target.closest('[data-view-comments]');
                if (toggle) {
                    const card = toggle.closest('.community-post');
                    const extras = card?.querySelectorAll('.sp-comment-extra') || [];
                    const expanded = toggle.dataset.expanded === '1';
                    extras.forEach((el) => { el.hidden = expanded; });
                    toggle.dataset.expanded = expanded ? '0' : '1';
                    toggle.textContent = expanded ? `View all ${card?.querySelectorAll('.sp-comment-thread').length ?? ''} comments`.trim() : 'Show less';
                    return;
                }
                const countBtn = e.target.closest('[data-scroll-comments]');
                if (countBtn) {
                    const input = document.getElementById('comment-' + countBtn.dataset.scrollComments);
                    input?.focus();
                    input?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            });

            // Check URL hash or initial tab
            if (location.hash === '#activities') {
                switchTab('activities', false);
            } else if (document.body.dataset.portalTab === 'community') {
                switchTab('community', false);
            }

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

            const postJson = async (url, body = null) => {
                const options = {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    credentials: 'same-origin',
                };

                if (body instanceof FormData) {
                    options.body = body;
                } else if (body) {
                    options.headers['Content-Type'] = 'application/json';
                    options.body = JSON.stringify(body);
                }

                const res = await fetch(url, options);
                if (!res.ok) {
                    const err = await res.json().catch(() => ({}));
                    throw new Error(err.message || 'Request failed');
                }
                return res.json();
            };

            document.querySelectorAll('[data-comment-form]').forEach((form) => {
                form.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const card = form.closest('.community-post');
                    const input = form.querySelector('input[name="body"]');
                    const submitBtn = form.querySelector('button[type="submit"]');
                    if (!card || !input || !input.value.trim() || submitBtn.disabled) return;
                    if (!window.OrgActionConfirmations.confirmSubmit(e)) return;

                    submitBtn.disabled = true;
                    try {
                        const body = new FormData(form);
                        const data = await postJson(form.action, body);
                        const list = card.querySelector('[data-comments]');
                        if (list && data.comment) {
                            const row = document.createElement('div');
                            row.className = 'sp-comment';
                            const avatar = document.createElement('span');
                            avatar.className = 'sp-comment-avatar';
                            avatar.textContent = (data.comment.student_name || '?').charAt(0).toUpperCase();
                            const body = document.createElement('div');
                            body.className = 'sp-comment-body';
                            const head = document.createElement('div');
                            head.className = 'sp-comment-head';
                            const name = document.createElement('strong');
                            name.textContent = data.comment.student_name;
                            head.appendChild(name);
                            const text = document.createElement('span');
                            text.textContent = data.comment.body;
                            body.appendChild(head);
                            body.appendChild(text);
                            row.appendChild(avatar);
                            row.appendChild(body);
                            list.appendChild(row);
                        }
                        const countEl = card.querySelector('[data-comments-count]');
                        if (countEl) {
                            const countBtn = countEl.querySelector('.sp-comments-count-btn');
                            const label = `${data.comments_count} comment${data.comments_count === 1 ? '' : 's'}`;
                            if (countBtn) countBtn.textContent = label;
                            else countEl.textContent = label;
                        }
                        input.value = '';
                    } catch (err) {
                        alert('Could not post comment. Try again.');
                    } finally {
                        submitBtn.disabled = false;
                    }
                });
            });

            // Existing five-row dashboard tables retain their own page state.
            const initTablePagination = () => {
                document.querySelectorAll('[data-sp-paginated-card]').forEach((card) => {
                    const table = card.querySelector('[data-sp-table]');
                    const paginationContainer = card.querySelector('[data-sp-pagination]');
                    if (!table || !paginationContainer) return;

                    const rows = Array.from(table.querySelectorAll('tbody tr.sp-act-row'));
                    const pageSize = 5;
                    const totalRows = rows.length;

                    if (totalRows <= pageSize) {
                        rows.forEach((row) => (row.style.display = ''));
                        if (totalRows === 0) {
                            paginationContainer.style.display = 'none';
                        } else {
                            paginationContainer.innerHTML = `
                                <button type="button" class="sp-page-btn sp-page-nav is-disabled" disabled>Previous</button>
                                <span aria-live="polite">Page 1 of 1</span>
                                <button type="button" class="sp-page-btn sp-page-nav is-disabled" disabled>Next</button>
                            `;
                        }
                        return;
                    }

                    paginationContainer.style.display = 'flex';
                    const totalPages = Math.ceil(totalRows / pageSize);
                    let currentPage = 1;

                    const renderPage = (page) => {
                        currentPage = page;
                        const start = (page - 1) * pageSize;
                        const end = start + pageSize;

                        rows.forEach((row, idx) => {
                            row.style.display = idx >= start && idx < end ? '' : 'none';
                        });

                        renderControls();
                    };

                    const renderControls = () => {
                        paginationContainer.innerHTML = '';

                        // Previous page
                        const prevBtn = document.createElement('button');
                        prevBtn.type = 'button';
                        prevBtn.className = 'sp-page-btn sp-page-nav' + (currentPage === 1 ? ' is-disabled' : '');
                        prevBtn.textContent = 'Previous';
                        prevBtn.title = 'Previous page';
                        prevBtn.disabled = currentPage === 1;
                        prevBtn.addEventListener('click', () => {
                            if (currentPage > 1) renderPage(currentPage - 1);
                        });
                        paginationContainer.appendChild(prevBtn);

                        const position = document.createElement('span');
                        position.textContent = `Page ${currentPage} of ${totalPages}`;
                        position.setAttribute('aria-live', 'polite');
                        paginationContainer.appendChild(position);

                        // Next page
                        const nextBtn = document.createElement('button');
                        nextBtn.type = 'button';
                        nextBtn.className = 'sp-page-btn sp-page-nav' + (currentPage === totalPages ? ' is-disabled' : '');
                        nextBtn.textContent = 'Next';
                        nextBtn.title = 'Next page';
                        nextBtn.disabled = currentPage === totalPages;
                        nextBtn.addEventListener('click', () => {
                            if (currentPage < totalPages) renderPage(currentPage + 1);
                        });
                        paginationContainer.appendChild(nextBtn);
                    };

                    renderPage(1);
                });
            };

            initTablePagination();

            // Announcement Details Modal Handler
            const modalEl = document.getElementById('spAnnouncementModal');
            const modalTitle = document.getElementById('spModalTitle');
            const modalBody = document.getElementById('spModalBody');
            const modalAuthor = document.getElementById('spModalAuthor');
            const modalTime = document.getElementById('spModalTime');
            const modalPriority = document.getElementById('spModalPriority');
            const modalCloseBtn = document.getElementById('spModalCloseBtn');
            const modalDoneBtn = document.getElementById('spModalDoneBtn');

            const openAnnouncementModal = (btn) => {
                if (!modalEl) return;
                const title = btn.dataset.modalTitle || 'Announcement';
                const body = btn.dataset.modalBody || '';
                const author = btn.dataset.modalAuthor || 'OSO Admin';
                const time = btn.dataset.modalTime || 'Recent';
                const priority = (btn.dataset.modalPriority || 'normal').toLowerCase();

                if (modalTitle) modalTitle.textContent = title;
                if (modalBody) modalBody.textContent = body;
                if (modalAuthor) modalAuthor.textContent = author;
                if (modalTime) {
                    const timeSpan = modalTime.querySelector('span');
                    if (timeSpan) timeSpan.textContent = time;
                }
                if (modalPriority) {
                    modalPriority.className = `sp-chip sp-chip-${priority}`;
                    modalPriority.innerHTML = `<i class="bi bi-circle-fill"></i> ${priority.charAt(0).toUpperCase() + priority.slice(1)}`;
                }

                modalEl.classList.add('is-open');
                modalEl.setAttribute('aria-hidden', 'false');
                document.body.style.overflow = 'hidden';
            };
            const requestedAnnouncement = new URLSearchParams(window.location.search).get('announcement');
            if (requestedAnnouncement && /^\d+$/.test(requestedAnnouncement)) {
                const requestedRow = document.querySelector(`[data-announcement-id="${requestedAnnouncement}"]`);
                if (requestedRow) {
                    switchTab('announcements', false);
                    openAnnouncementModal(requestedRow);
                }
            }

            const closeAnnouncementModal = () => {
                if (!modalEl) return;
                modalEl.classList.remove('is-open');
                modalEl.setAttribute('aria-hidden', 'true');
                document.body.style.overflow = '';
            };

            document.addEventListener('click', (e) => {
                const btn = e.target.closest('.sp-see-details-btn, .sp-announce-tbl-row, .sp-announcement-row-card');
                if (btn) {
                    e.preventDefault();
                    openAnnouncementModal(btn);
                }
            });

            if (modalCloseBtn) modalCloseBtn.addEventListener('click', closeAnnouncementModal);
            if (modalDoneBtn) modalDoneBtn.addEventListener('click', closeAnnouncementModal);
            if (modalEl) {
                modalEl.addEventListener('click', (e) => {
                    if (e.target === modalEl) closeAnnouncementModal();
                });
            }

            // Close modal or sidebar on Escape
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    if (modalEl?.classList.contains('is-open')) {
                        closeAnnouncementModal();
                    } else if (actModalEl?.classList.contains('is-open')) {
                        closeActModal();
                    } else if (sidebar?.classList.contains('is-open')) {
                        closeSidebar(true);
                    }
                }
            });

            // -------------------------------------------------------------
            // Activities Panel Interactive Handlers (Frontend-only)
            // -------------------------------------------------------------
            const actItems = document.querySelectorAll('[data-act-item]');
            const actSearchInput = document.getElementById('spActSearchInput');
            const actSearchClear = document.getElementById('spActSearchClear');
            const actFilterPills = document.querySelectorAll('[data-act-filter]');
            const actEmptyState = document.getElementById('spActEmptyState');
            const actResetBtn = document.getElementById('spActResetFilterBtn');
            const viewGridBtn = document.getElementById('spViewGridBtn');
            const viewListBtn = document.getElementById('spViewListBtn');
            const gridViewEl = document.getElementById('spActivitiesGridView');
            const listViewEl = document.getElementById('spActivitiesListView');
            const listPaginationEl = document.getElementById('spActListPagination');

            let currentActFilter = 'all';
            const LIST_PAGE_SIZE = 7;
            let currentListPage = 1;

            const updateListPagination = () => {
                if (!listViewEl) return;
                const rows = Array.from(listViewEl.querySelectorAll('tbody tr[data-act-item]'));
                const matchingRows = rows.filter((r) => r.dataset.filterMatch !== 'false');
                const totalRows = matchingRows.length;

                if (!listPaginationEl) return;

                // When total is 7 or fewer, hide pagination and show all matching
                if (totalRows <= LIST_PAGE_SIZE) {
                    listPaginationEl.style.display = 'none';
                    matchingRows.forEach((row) => (row.style.display = ''));
                    return;
                }

                listPaginationEl.style.display = 'flex';
                const totalPages = Math.ceil(totalRows / LIST_PAGE_SIZE);

                if (currentListPage > totalPages) currentListPage = totalPages;
                if (currentListPage < 1) currentListPage = 1;

                const renderListPage = (page) => {
                    currentListPage = page;
                    const start = (page - 1) * LIST_PAGE_SIZE;
                    const end = start + LIST_PAGE_SIZE;

                    matchingRows.forEach((row, idx) => {
                        row.style.display = (idx >= start && idx < end) ? '' : 'none';
                    });

                    renderListControls(totalPages);
                };

                const renderListControls = (totalPages) => {
                    listPaginationEl.innerHTML = '';

                    // Previous page
                    const prevBtn = document.createElement('button');
                    prevBtn.type = 'button';
                    prevBtn.className = 'sp-page-btn sp-page-nav' + (currentListPage === 1 ? ' is-disabled' : '');
                    prevBtn.textContent = 'Previous';
                    prevBtn.title = 'Previous page';
                    prevBtn.disabled = currentListPage === 1;
                    prevBtn.addEventListener('click', () => {
                        if (currentListPage > 1) renderListPage(currentListPage - 1);
                    });
                    listPaginationEl.appendChild(prevBtn);

                    const position = document.createElement('span');
                    position.textContent = `Page ${currentListPage} of ${totalPages}`;
                    position.setAttribute('aria-live', 'polite');
                    listPaginationEl.appendChild(position);

                    // Next page
                    const nextBtn = document.createElement('button');
                    nextBtn.type = 'button';
                    nextBtn.className = 'sp-page-btn sp-page-nav' + (currentListPage === totalPages ? ' is-disabled' : '');
                    nextBtn.textContent = 'Next';
                    nextBtn.title = 'Next page';
                    nextBtn.disabled = currentListPage === totalPages;
                    nextBtn.addEventListener('click', () => {
                        if (currentListPage < totalPages) renderListPage(currentListPage + 1);
                    });
                    listPaginationEl.appendChild(nextBtn);
                };

                renderListPage(currentListPage);
            };

            const filterActivities = () => {
                const query = (actSearchInput?.value || '').trim().toLowerCase();
                let visibleCount = 0;

                actItems.forEach((item) => {
                    const status = item.getAttribute('data-status') || '';
                    const title = item.getAttribute('data-title') || '';
                    const location = item.getAttribute('data-location') || '';
                    const desc = item.getAttribute('data-desc') || '';

                    const matchesStatus = currentActFilter === 'all' || status === currentActFilter;
                    const matchesQuery = !query || title.includes(query) || location.includes(query) || desc.includes(query);

                    const show = matchesStatus && matchesQuery;
                    item.hidden = !show;
                    item.dataset.filterMatch = show ? 'true' : 'false';
                    if (item.tagName === 'TR') {
                        item.style.display = show ? '' : 'none';
                    }
                    if (show) visibleCount++;
                });

                if (actEmptyState) {
                    actEmptyState.hidden = visibleCount > 0;
                }
                if (actSearchClear) {
                    actSearchClear.hidden = !query;
                }

                currentListPage = 1;
                updateListPagination();
            };

            actFilterPills.forEach((pill) => {
                pill.addEventListener('click', () => {
                    actFilterPills.forEach((p) => {
                        p.classList.remove('is-active');
                        p.setAttribute('aria-selected', 'false');
                    });
                    pill.classList.add('is-active');
                    pill.setAttribute('aria-selected', 'true');
                    currentActFilter = pill.getAttribute('data-act-filter') || 'all';
                    filterActivities();
                });
            });

            actSearchInput?.addEventListener('input', filterActivities);
            actSearchClear?.addEventListener('click', () => {
                if (actSearchInput) actSearchInput.value = '';
                filterActivities();
                actSearchInput?.focus();
            });

            actResetBtn?.addEventListener('click', () => {
                if (actSearchInput) actSearchInput.value = '';
                const allPill = document.querySelector('[data-act-filter="all"]');
                if (allPill) allPill.click();
                else {
                    currentActFilter = 'all';
                    filterActivities();
                }
            });

            viewGridBtn?.addEventListener('click', () => {
                viewGridBtn.classList.add('is-active');
                viewListBtn?.classList.remove('is-active');
                if (gridViewEl) gridViewEl.hidden = false;
                if (listViewEl) listViewEl.hidden = true;
            });

            viewListBtn?.addEventListener('click', () => {
                viewListBtn.classList.add('is-active');
                viewGridBtn?.classList.remove('is-active');
                if (gridViewEl) gridViewEl.hidden = true;
                if (listViewEl) {
                    listViewEl.hidden = false;
                    updateListPagination();
                }
            });

            // Initial list pagination check
            updateListPagination();

            // Activity Details Modal Handlers
            // -------------------------------------------------------------
            // Multi-Tab Activity Details Modal & Lightbox Handlers
            // -------------------------------------------------------------
            const actModalEl = document.getElementById('spActivityModal');
            const actModalCloseBtn = document.getElementById('spActModalCloseBtn');
            const actModalDoneBtn = document.getElementById('spActModalDoneBtn');
            const actModalTitle = document.getElementById('spActModalTitle');
            const actModalStatus = document.getElementById('spActModalStatus');
            const actModalOrg = document.getElementById('spActModalOrg');
            const actModalDate = document.getElementById('spActModalDate');
            const actModalTime = document.getElementById('spActModalTime');
            const actModalLocation = document.getElementById('spActModalLocation');
            const actModalDesc = document.getElementById('spActModalDesc');
            const actModalObjectives = document.getElementById('spActModalObjectives');
            const actModalAgenda = document.getElementById('spActModalAgenda');
            const actModalTabs = document.querySelectorAll('.sp-act-modal-tab');
            const actTabPanels = document.querySelectorAll('.sp-act-tab-panel');

            // Lightbox elements
            const actLightbox = document.getElementById('spActLightbox');
            const actLightboxImg = document.getElementById('spActLightboxImg');
            const actLightboxCaption = document.getElementById('spActLightboxCaption');
            const actLightboxClose = document.getElementById('spActLightboxClose');

            const formatMoney = (amount) => {
                return '₱' + Number(amount || 0).toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
            };

            const switchModalTab = (tabName) => {
                actModalTabs.forEach((tab) => {
                    const active = tab.dataset.actTab === tabName;
                    tab.classList.toggle('is-active', active);
                    tab.setAttribute('aria-selected', active ? 'true' : 'false');
                });

                actTabPanels.forEach((panel) => {
                    const active = panel.dataset.tabPanel === tabName;
                    panel.hidden = !active;
                    panel.classList.toggle('is-active', active);
                });
            };

            actModalTabs.forEach((tab) => {
                tab.addEventListener('click', () => {
                    const tabName = tab.dataset.actTab;
                    if (tabName) switchModalTab(tabName);
                });
            });

            const openActModal = (btn) => {
                if (!actModalEl) return;

                let data = null;
                if (btn.dataset.actDetails) {
                    try {
                        data = JSON.parse(btn.dataset.actDetails);
                    } catch (e) {
                        data = null;
                    }
                }

                if (!data) {
                    const title = btn.dataset.title || 'Activity Details';
                    const status = (btn.dataset.status || 'upcoming').toLowerCase();
                    const date = btn.dataset.date || 'Date TBA';
                    const time = btn.dataset.time || 'Time TBA';
                    const loc = btn.dataset.location || 'BatStateU Campus';
                    const desc = btn.dataset.desc || 'No detailed description provided.';

                    data = {
                        id: btn.dataset.id || 'act-sample',
                        title: title,
                        organizer: 'BatStateU Student Organization',
                        status: status,
                        date: date,
                        time: time,
                        location: loc,
                        description: desc,
                        objectives: [
                            'Foster collaborative leadership and student engagement.',
                            'Deliver academic and organizational development outcomes.',
                            'Ensure transparent and compliant fund liquidation on OrgChain.'
                        ],
                        agenda: [
                            { time: 'Opening', title: 'Assembly, Attendance & Opening Remarks', lead: 'Secretariat' },
                            { time: 'Main Session', title: title, lead: 'Organizing Committee' },
                            { time: 'Closing', title: 'Evaluation, Certificates & Documentation', lead: 'Executive Board' }
                        ],
                        participants: {
                            registered: 185,
                            capacity: 250,
                            target: 'BatStateU Students & Officers',
                        },
                        photos: [
                            { url: '{{ asset("voting-assets/img/Bg_SSC.jpg") }}', caption: 'Plenary hall assembly in session', tag: 'Session' },
                            { url: '{{ asset("voting-assets/img/ssc_pic.jpg") }}', caption: 'Student leaders collaborating', tag: 'Delegates' }
                        ],
                        budget: {
                            allocated: 35000,
                            utilized: 31850,
                            remaining: 3150,
                            rate: 91.0,
                            tx_hash: '0x8f7e6d5c4b3a210987654321fedcba0987654321',
                            items: [
                                { category: 'Venue Logistics & Audio-Visual Setup', allocated: 15750, spent: 15200, variance: 550, status: 'Receipt Verified' },
                                { category: 'Participant Kits & Handouts', allocated: 12250, spent: 11800, variance: 450, status: 'Receipt Verified' },
                                { category: 'Refreshments & Volunteer Tokens', allocated: 7000, spent: 4850, variance: 2150, status: 'Audited by OSO' }
                            ]
                        }
                    };
                }

                actModalEl._actData = data;

                // Reset to overview tab
                switchModalTab('overview');

                // Header Info
                if (actModalTitle) actModalTitle.textContent = data.title;
                if (actModalDate) actModalDate.textContent = data.date;
                if (actModalTime) actModalTime.textContent = data.time;
                if (actModalLocation) actModalLocation.textContent = data.location;
                if (actModalOrg) {
                    const span = actModalOrg.querySelector('span');
                    if (span) span.textContent = data.organizer || 'Student Organization';
                }

                if (actModalStatus) {
                    const st = (data.status || 'upcoming').toLowerCase();
                    actModalStatus.className = `sp-chip sp-chip-${st}`;
                    actModalStatus.innerHTML = `<i class="bi bi-circle-fill"></i> ${st.charAt(0).toUpperCase() + st.slice(1)}`;
                }

                // Tab 1: Overview
                if (actModalDesc) actModalDesc.textContent = data.description || 'No description available.';

                if (actModalObjectives) {
                    actModalObjectives.innerHTML = (data.objectives || [])
                        .map((obj) => `<li><i class="bi bi-check2-circle"></i> <span>${obj}</span></li>`)
                        .join('');
                }

                if (actModalAgenda) {
                    actModalAgenda.innerHTML = (data.agenda || [])
                        .map((ag) => `
                            <div class="sp-act-timeline-item">
                                <div class="sp-act-timeline-dot"></div>
                                <div class="sp-act-timeline-content">
                                    <div class="sp-act-timeline-head">
                                        <span class="sp-act-time-pill"><i class="bi bi-clock"></i> ${ag.time}</span>
                                        <strong>${ag.title}</strong>
                                    </div>
                                    <small class="sp-act-lead"><i class="bi bi-person-badge"></i> Lead: ${ag.lead}</small>
                                </div>
                            </div>
                        `)
                        .join('');
                }

                // Tab 2: Participants
                const part = data.participants || { registered: 185, capacity: 250, target: 'All Students' };
                const regCount = Number(part.registered || 0);
                const capCount = Number(part.capacity || 250);
                const capPct = Math.min(100, Math.round((regCount / Math.max(capCount, 1)) * 100));

                const partCountEl = document.getElementById('spActTabPartCount');
                if (partCountEl) partCountEl.textContent = regCount;

                const regEl = document.getElementById('spActPartRegistered');
                if (regEl) regEl.textContent = regCount;

                const capEl = document.getElementById('spActPartCapacity');
                if (capEl) capEl.textContent = capCount;

                const pctEl = document.getElementById('spActPartPct');
                if (pctEl) pctEl.textContent = capPct + '%';

                const targetEl = document.getElementById('spActPartTarget');
                if (targetEl) targetEl.textContent = part.target || 'BatStateU Students';

                const ratioEl = document.getElementById('spActPartRatioText');
                if (ratioEl) ratioEl.textContent = `${regCount} / ${capCount} Slots Filled`;

                const barFillEl = document.getElementById('spActPartBarFill');
                if (barFillEl) {
                    barFillEl.style.width = `${capPct}%`;
                    barFillEl.className = `sp-act-part-bar-fill ${capPct >= 90 ? 'is-full' : (capPct >= 70 ? 'is-high' : '')}`;
                }

                // Tab 3: Photos
                const photos = data.photos || [];
                const photoCountEl = document.getElementById('spActTabPhotoCount');
                if (photoCountEl) photoCountEl.textContent = photos.length;

                const photoTagEl = document.getElementById('spActPhotoCountTag');
                if (photoTagEl) photoTagEl.textContent = `${photos.length} photos`;

                const galleryEl = document.getElementById('spActPhotoGallery');
                const noPhotosEl = document.getElementById('spActNoPhotos');

                if (photos.length === 0) {
                    if (galleryEl) galleryEl.innerHTML = '';
                    if (noPhotosEl) noPhotosEl.hidden = false;
                } else {
                    if (noPhotosEl) noPhotosEl.hidden = true;
                    if (galleryEl) {
                        galleryEl.innerHTML = photos
                            .map((p, idx) => `
                                <div class="sp-act-photo-item" data-photo-idx="${idx}">
                                    <img src="${p.url}" alt="${p.caption || 'Event photo'}" loading="lazy">
                                    <div class="sp-photo-overlay">
                                        <span class="sp-photo-tag">${p.tag || 'Highlight'}</span>
                                        <p class="sp-photo-caption">${p.caption || ''}</p>
                                        <span class="sp-photo-zoom-icon"><i class="bi bi-arrows-fullscreen"></i></span>
                                    </div>
                                </div>
                            `)
                            .join('');

                        galleryEl.querySelectorAll('.sp-act-photo-item').forEach((item) => {
                            item.addEventListener('click', () => {
                                const idx = item.dataset.photoIdx;
                                const ph = photos[idx];
                                if (ph && actLightbox && actLightboxImg) {
                                    actLightboxImg.src = ph.url;
                                    if (actLightboxCaption) actLightboxCaption.textContent = ph.caption || '';
                                    actLightbox.hidden = false;
                                    actLightbox.setAttribute('aria-hidden', 'false');
                                }
                            });
                        });
                    }
                }

                // Tab 4: Budget & Transparency
                const bgt = data.budget || {
                    allocated: 35000,
                    utilized: 31850,
                    remaining: 3150,
                    rate: 91.0,
                    tx_hash: '0x7f8b9a2c4e1d5f309a826471e8c9b0a1d4f2e7c9',
                    items: []
                };

                const budgetPctBadge = document.getElementById('spActTabBudgetPct');
                if (budgetPctBadge) budgetPctBadge.textContent = `${Math.round(bgt.rate || 0)}%`;

                const allocEl = document.getElementById('spActBudgetAllocated');
                if (allocEl) allocEl.textContent = formatMoney(bgt.allocated);

                const utilEl = document.getElementById('spActBudgetUtilized');
                if (utilEl) utilEl.textContent = formatMoney(bgt.utilized);

                const remEl = document.getElementById('spActBudgetRemaining');
                if (remEl) remEl.textContent = formatMoney(bgt.remaining);

                const rateEl = document.getElementById('spActBudgetRate');
                if (rateEl) rateEl.textContent = `${bgt.rate || 0}%`;

                const txHashEl = document.getElementById('spActTxHash');
                if (txHashEl) txHashEl.textContent = `TX: ${bgt.tx_hash || '0x' + Math.random().toString(16).substr(2, 40)}`;

                const bgtTbody = document.getElementById('spActBudgetItemsTbody');
                if (bgtTbody) {
                    const items = bgt.items && bgt.items.length > 0 ? bgt.items : [
                        { category: 'Venue Logistics & Audio-Visual Setup', allocated: bgt.allocated * 0.45, spent: bgt.utilized * 0.45, variance: (bgt.allocated - bgt.utilized) * 0.45, status: 'Receipt Verified' },
                        { category: 'Participant Handouts & Leadership Kits', allocated: bgt.allocated * 0.35, spent: bgt.utilized * 0.35, variance: (bgt.allocated - bgt.utilized) * 0.35, status: 'Receipt Verified' },
                        { category: 'Refreshments & Volunteer Tokens', allocated: bgt.allocated * 0.20, spent: bgt.utilized * 0.20, variance: (bgt.allocated - bgt.utilized) * 0.20, status: 'Audited by OSO' }
                    ];

                    bgtTbody.innerHTML = items
                        .map((it) => `
                            <tr>
                                <td><strong>${it.category}</strong></td>
                                <td>${formatMoney(it.allocated)}</td>
                                <td class="sp-text-spent">${formatMoney(it.spent)}</td>
                                <td class="sp-text-surplus">${formatMoney(it.variance)}</td>
                                <td class="text-end">
                                    <span class="sp-chip sp-chip-completed">
                                        <i class="bi bi-shield-check"></i> ${it.status || 'Verified'}
                                    </span>
                                </td>
                            </tr>
                        `)
                        .join('');
                }

                actModalEl.classList.add('is-open');
                actModalEl.setAttribute('aria-hidden', 'false');
                document.body.style.overflow = 'hidden';
            };

            const closeActModal = () => {
                if (!actModalEl) return;
                actModalEl.classList.remove('is-open');
                actModalEl.setAttribute('aria-hidden', 'true');
                document.body.style.overflow = '';
            };

            const closeLightbox = () => {
                if (!actLightbox) return;
                actLightbox.hidden = true;
                actLightbox.setAttribute('aria-hidden', 'true');
                if (actLightboxImg) actLightboxImg.src = '';
            };

            if (actLightboxClose) actLightboxClose.addEventListener('click', closeLightbox);
            if (actLightbox) {
                actLightbox.addEventListener('click', (e) => {
                    if (e.target === actLightbox) closeLightbox();
                });
            }

            // Calendar Export simulation
            const calendarBtn = document.getElementById('spActModalCalendarBtn');
            if (calendarBtn) {
                calendarBtn.addEventListener('click', () => {
                    const data = actModalEl._actData;
                    const title = data?.title || 'BatStateU Campus Activity';
                    const loc = data?.location || 'BatStateU';
                    const desc = data?.description || 'OrgChain Student Activity';

                    const icsData = [
                        'BEGIN:VCALENDAR',
                        'VERSION:2.0',
                        'PRODID:-//OrgChain//Student Portal//EN',
                        'BEGIN:VEVENT',
                        `SUMMARY:${title}`,
                        `DESCRIPTION:${desc}`,
                        `LOCATION:${loc}`,
                        `DTSTART:${new Date().toISOString().replace(/[-:]/g, '').split('.')[0]}Z`,
                        `DTEND:${new Date(Date.now() + 3600000 * 3).toISOString().replace(/[-:]/g, '').split('.')[0]}Z`,
                        'END:VEVENT',
                        'END:VCALENDAR'
                    ].join('\r\n');

                    const blob = new Blob([icsData], { type: 'text/calendar;charset=utf-8;' });
                    const link = document.createElement('a');
                    link.href = URL.createObjectURL(blob);
                    link.setAttribute('download', `${title.replace(/\s+/g, '_').toLowerCase()}.ics`);
                    document.body.appendChild(link);
                    link.click();
                    link.remove();
                });
            }

            // Discuss button inside modal
            const modalDiscussBtn = document.getElementById('spActModalDiscussBtn');
            if (modalDiscussBtn) {
                modalDiscussBtn.addEventListener('click', () => {
                    const data = actModalEl._actData;
                    closeActModal();
                    switchTab('community', true);
                    if (data?.id) {
                        const sel = document.querySelector('select[name="activity_id"]');
                        if (sel) sel.value = data.id;
                    }
                    const composer = document.querySelector('textarea[name="body"]');
                    if (composer) {
                        composer.focus();
                        composer.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                });
            }

            document.addEventListener('click', (e) => {
                const trigger = e.target.closest('[data-act-modal-trigger]');
                if (trigger) {
                    e.preventDefault();
                    openActModal(trigger);
                    return;
                }

                // Discuss button from activity card
                const discussBtn = e.target.closest('.sp-act-btn-discuss');
                if (discussBtn) {
                    e.preventDefault();
                    switchTab('community', true);
                    const actId = discussBtn.dataset.actId;
                    if (actId) {
                        const sel = document.querySelector('select[name="activity_id"]');
                        if (sel) sel.value = actId;
                    }
                    const composer = document.querySelector('textarea[name="body"]');
                    if (composer) {
                        composer.focus();
                        composer.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                }
            });

            if (actModalCloseBtn) actModalCloseBtn.addEventListener('click', closeActModal);
            if (actModalDoneBtn) actModalDoneBtn.addEventListener('click', closeActModal);
            if (actModalEl) {
                actModalEl.addEventListener('click', (e) => {
                    if (e.target === actModalEl) closeActModal();
                });
            }

            // Escape handler
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    if (actLightbox && !actLightbox.hidden) {
                        closeLightbox();
                    } else if (actModalEl?.classList.contains('is-open')) {
                        closeActModal();
                    }
                }
            });
        })();
    </script>
</body>
</html>
