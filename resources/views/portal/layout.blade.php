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
        <link rel="stylesheet" href="{{ asset('css/landing.css') }}">
    @endif
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
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
                    <span>Student Portal</span>
                </div>
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

            @if (!empty($student->program) || !empty($student->college))
                <div class="sp-profile-meta">
                    @if (!empty($student->program))
                        <p><i class="bi bi-mortarboard"></i> {{ $student->program }}</p>
                    @endif
                    @if (!empty($student->college))
                        <p><i class="bi bi-building"></i> {{ $student->college }}</p>
                    @endif
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
                    <div class="sp-search-box" id="spTopSearchBox" @if(($tab ?? '') === 'activities' || ($tab ?? '') === 'announcements' || ($tab ?? '') === 'tosa') style="display: none;" @endif>
                        <i class="bi bi-search"></i>
                        <input type="search" placeholder="Search activities, reports, and more..." aria-label="Search activities, reports, and more">
                    </div>
                    <button type="button" class="sp-settings-btn" id="spSettingsBtn" aria-label="Settings" title="Settings">
                        <i class="bi bi-gear"></i>
                    </button>
                    <button type="button" class="sp-bell-btn" aria-label="Notifications" title="Notifications">
                        <i class="bi bi-bell"></i>
                    </button>
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
                            <form method="post" action="{{ route('student.logout') }}" class="sp-dropdown-logout">
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
                    <div class="sp-flash" role="status">
                        <i class="bi bi-check-circle-fill"></i>
                        <span>{{ session('status') }}</span>
                    </div>
                @endif

                @yield('content')
            </div>
        </div>
    </div>

    {{-- Mobile sidebar overlay --}}
    <div class="sp-overlay" id="spOverlay" hidden></div>

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

            const closeSidebar = () => {
                if (!sidebar) return;
                sidebar.classList.remove('is-open');
                if (overlay) overlay.setAttribute('hidden', '');
                if (menuToggle) menuToggle.setAttribute('aria-expanded', 'false');
                document.body.classList.remove('sp-no-scroll');
            };

            const openSidebar = () => {
                if (!sidebar) return;
                sidebar.classList.add('is-open');
                if (overlay) overlay.removeAttribute('hidden');
                if (menuToggle) menuToggle.setAttribute('aria-expanded', 'true');
                document.body.classList.add('sp-no-scroll');
            };

            menuToggle?.addEventListener('click', () => {
                if (sidebar?.classList.contains('is-open')) closeSidebar();
                else openSidebar();
            });

            overlay?.addEventListener('click', closeSidebar);

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
                    topSearchBox.style.display = (tab === 'activities' || tab === 'announcements' || tab === 'tosa') ? 'none' : '';
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

            // -------------------------------------------------------------
            // Announcements Multi-Filter & < 1, 2, 3 > Pagination Engine
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
                    if (!allRows.length) return;

                    // 1. Filter rows
                    const filtered = allRows.filter((row) => {
                        const cat = row.dataset.announceCat || '';
                        const prio = row.dataset.announcePriority || '';
                        const search = row.dataset.announceSearch || '';

                        const matchCat = activeCat === 'all' || cat === activeCat || cat.includes(activeCat);
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

                    // 7. Page Buttons < 1, 2, 3 >
                    if (pageNumbersContainer) {
                        pageNumbersContainer.innerHTML = '';
                        for (let p = 1; p <= totalPages; p++) {
                            const btn = document.createElement('button');
                            btn.type = 'button';
                            btn.className = `sp-ann-page-btn ${p === currentPage ? 'is-active' : ''}`;
                            btn.textContent = p;
                            btn.setAttribute('aria-label', `Page ${p}`);
                            btn.addEventListener('click', () => {
                                currentPage = p;
                                renderTable();
                            });
                            pageNumbersContainer.appendChild(btn);
                        }
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
            // TOSA Applications (Submissions Table) Handlers
            // -------------------------------------------------------------
            const formatNow = () => {
                const now = new Date();
                const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                const month = months[now.getMonth()];
                const day = now.getDate();
                const year = now.getFullYear();
                let hours = now.getHours();
                const minutes = String(now.getMinutes()).padStart(2, '0');
                const ampm = hours >= 12 ? 'PM' : 'AM';
                hours = hours % 12 || 12;
                return `${month} ${day}, ${year} • ${hours}:${minutes} ${ampm}`;
            };

            window.updateTosaProgress = function () {
                const totalReqs = 7;
                const uploadedElements = document.querySelectorAll('#spTosaSubmissionsTbody .sp-status-wrap.is-uploaded');
                const uploadedCount = uploadedElements ? uploadedElements.length : 0;

                const stepNode1 = document.getElementById('tosaStepNode_1');
                const stepPill1 = document.getElementById('tosaStepPill_1');
                const stepTime1 = document.getElementById('tosaStepTime_1');
                const stepLine1 = document.getElementById('tosaStepLine_1');

                const stepNode2 = document.getElementById('tosaStepNode_2');
                const stepPill2 = document.getElementById('tosaStepPill_2');
                const stepTime2 = document.getElementById('tosaStepTime_2');
                const stepLine2 = document.getElementById('tosaStepLine_2');

                if (uploadedCount === 0) {
                    if (stepNode1) stepNode1.className = 'sp-tosa-timeline-step is-inprogress';
                    if (stepPill1) {
                        stepPill1.className = 'sp-tosa-node-pill pill-inprogress';
                        stepPill1.textContent = 'In Progress';
                    }
                    if (stepTime1) stepTime1.innerHTML = '<span>Pending Documents</span>';
                    if (stepLine1) stepLine1.className = 'sp-tosa-timeline-line is-dashed';

                    if (stepNode2) stepNode2.className = 'sp-tosa-timeline-step is-pending';
                    if (stepPill2) {
                        stepPill2.className = 'sp-tosa-node-pill pill-pending';
                        stepPill2.textContent = 'Pending';
                    }
                    if (stepTime2) stepTime2.innerHTML = '';
                    if (stepLine2) stepLine2.className = 'sp-tosa-timeline-line is-dashed';
                } else if (uploadedCount < totalReqs) {
                    if (stepNode1) stepNode1.className = 'sp-tosa-timeline-step is-inprogress';
                    if (stepPill1) {
                        stepPill1.className = 'sp-tosa-node-pill pill-inprogress';
                        stepPill1.textContent = `${uploadedCount}/${totalReqs} Uploaded`;
                    }
                    if (stepTime1) stepTime1.innerHTML = '<span>Uploading Files...</span>';
                    if (stepLine1) stepLine1.className = 'sp-tosa-timeline-line is-dashed';

                    if (stepNode2) stepNode2.className = 'sp-tosa-timeline-step is-pending';
                    if (stepPill2) {
                        stepPill2.className = 'sp-tosa-node-pill pill-pending';
                        stepPill2.textContent = 'Pending';
                    }
                    if (stepTime2) stepTime2.innerHTML = '';
                    if (stepLine2) stepLine2.className = 'sp-tosa-timeline-line is-dashed';
                } else {
                    // All 7 uploaded & submitted!
                    if (stepNode1) stepNode1.className = 'sp-tosa-timeline-step is-completed';
                    if (stepPill1) {
                        stepPill1.className = 'sp-tosa-node-pill pill-completed';
                        stepPill1.textContent = 'Completed';
                    }
                    if (stepTime1) {
                        const now = new Date();
                        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                        const dateStr = `${months[now.getMonth()]} ${now.getDate()}, ${now.getFullYear()}`;
                        let hours = now.getHours();
                        const minutes = String(now.getMinutes()).padStart(2, '0');
                        const ampm = hours >= 12 ? 'PM' : 'AM';
                        hours = hours % 12 || 12;
                        stepTime1.innerHTML = `<span>${dateStr}</span><span>${hours}:${minutes} ${ampm}</span>`;
                    }
                    if (stepLine1) stepLine1.className = 'sp-tosa-timeline-line is-green';

                    if (stepNode2) stepNode2.className = 'sp-tosa-timeline-step is-inprogress';
                    if (stepPill2) {
                        stepPill2.className = 'sp-tosa-node-pill pill-inprogress';
                        stepPill2.textContent = 'In Progress';
                    }
                    if (stepTime2) {
                        stepTime2.innerHTML = '<span>Reviewing CTC...</span>';
                    }
                    if (stepLine2) stepLine2.className = 'sp-tosa-timeline-line is-blue';
                }
            };

            window.handleTosaDocUpload = function (id, input) {
                if (!input.files || !input.files[0]) return;
                const file = input.files[0];
                const statusWrap = document.getElementById(`tosaStatus_${id}`);
                const actionsWrap = document.getElementById(`tosaActions_${id}`);

                if (statusWrap) {
                    statusWrap.className = 'sp-status-wrap is-uploaded';
                    statusWrap.innerHTML = `
                        <div class="sp-status-heading">
                            <i class="bi bi-check-circle-fill"></i>
                            <strong>Uploaded</strong>
                        </div>
                        <small id="tosaTime_${id}">Uploaded on ${formatNow()}</small>
                    `;
                }

                if (actionsWrap) {
                    actionsWrap.innerHTML = `
                        <button type="button" class="sp-tosa-btn-replace" onclick="document.getElementById('tosaInput_${id}')?.click()">
                            <i class="bi bi-arrow-repeat"></i>
                            <span>Replace File</span>
                        </button>
                        <button type="button" class="sp-tosa-btn-remove" onclick="window.handleTosaDocRemove?.(${id})">
                            <i class="bi bi-trash3"></i>
                            <span>Remove File</span>
                        </button>
                    `;
                }

                window.updateTosaProgress?.();
            };

            window.handleTosaDocRemove = function (id) {
                const input = document.getElementById(`tosaInput_${id}`);
                if (input) input.value = '';

                const statusWrap = document.getElementById(`tosaStatus_${id}`);
                const actionsWrap = document.getElementById(`tosaActions_${id}`);

                const isMissingRow = id === 7;
                const statusClass = isMissingRow ? 'is-missing' : 'is-pending';
                const statusText = isMissingRow ? 'Missing' : 'Pending';
                const iconClass = isMissingRow ? 'bi-x-circle' : 'bi-clock';
                const subText = isMissingRow ? 'Required' : 'Not uploaded yet';

                if (statusWrap) {
                    statusWrap.className = `sp-status-wrap ${statusClass}`;
                    statusWrap.innerHTML = `
                        <div class="sp-status-heading">
                            <i class="bi ${iconClass}"></i>
                            <strong>${statusText}</strong>
                        </div>
                        <small id="tosaTime_${id}">${subText}</small>
                    `;
                }

                if (actionsWrap) {
                    actionsWrap.innerHTML = `
                        <button type="button" class="sp-tosa-btn-upload" onclick="document.getElementById('tosaInput_${id}')?.click()">
                            <i class="bi bi-upload"></i>
                            <span>Upload</span>
                        </button>
                    `;
                }

                window.updateTosaProgress?.();
            };

            window.updateTosaProgress?.();

            document.getElementById('spTosaGuidelinesBtn')?.addEventListener('click', () => {
                const modalEl = document.getElementById('spAnnouncementModal');
                const modalTitle = document.getElementById('spModalTitle');
                const modalBody = document.getElementById('spModalBody');
                const modalAuthor = document.getElementById('spModalAuthor');
                const modalTime = document.getElementById('spModalTime');
                const modalPriority = document.getElementById('spModalPriority');

                if (modalEl && modalTitle && modalBody) {
                    modalTitle.textContent = 'TOSA Application File Guidelines';
                    modalBody.textContent = 'Please follow these mandatory document guidelines:\n\n1. Format: All documents must be in PDF format only.\n2. Resolution: Ensure scans are legible, colored CTC grades or clearance stamps clearly visible.\n3. File Size: Maximum file size is 25MB per document.\n4. Accomplished Forms: Must be fully signed by your College Dean or Organization Faculty Adviser.\n5. Naming Convention: [StudentNumber]_[DocumentType].pdf (e.g. 21-00123_Grades.pdf).';
                    if (modalAuthor) modalAuthor.textContent = 'Office of Student Organizations (OSO)';
                    if (modalTime) {
                        const span = modalTime.querySelector('span');
                        if (span) span.textContent = 'Official Submission Protocol';
                    }
                    if (modalPriority) {
                        modalPriority.className = 'sp-chip sp-chip-normal';
                        modalPriority.innerHTML = '<i class="bi bi-shield-check"></i> Standard Guidelines';
                    }
                    modalEl.classList.add('is-open');
                    modalEl.setAttribute('aria-hidden', 'false');
                }
            });

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

            // 2. Tag People
            const tagBtn = document.getElementById('spComposerTagBtn');
            const tagPopover = document.getElementById('spTagPopover');
            const tagClose = document.getElementById('spTagPopoverClose');
            const chipTags = document.getElementById('spChipTags');
            const chipTagsName = document.getElementById('spChipTagsName');
            const removeTagsBtn = document.getElementById('spRemoveTagsBtn');
            const taggedInput = document.getElementById('spComposerTaggedInput');
            const tagSearchInput = document.getElementById('spTagSearchInput');

            tagBtn?.addEventListener('click', (e) => {
                e.stopPropagation();
                const willOpen = tagPopover?.hidden;
                closeAllComposerPopovers();
                if (tagPopover) tagPopover.hidden = !willOpen;
                if (willOpen && tagSearchInput) setTimeout(() => tagSearchInput.focus(), 50);
            });

            tagClose?.addEventListener('click', (e) => {
                e.stopPropagation();
                if (tagPopover) tagPopover.hidden = true;
            });

            document.querySelectorAll('.sp-tag-option').forEach((opt) => {
                opt.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const tagVal = opt.dataset.tag || opt.textContent.trim();
                    if (taggedInput) taggedInput.value = tagVal;
                    if (chipTagsName) chipTagsName.textContent = 'With: ' + tagVal;
                    if (chipTags) chipTags.hidden = false;
                    if (tagPopover) tagPopover.hidden = true;
                    updateChipsVisibility();
                });
            });

            tagSearchInput?.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    const val = tagSearchInput.value.trim();
                    if (val) {
                        if (taggedInput) taggedInput.value = val;
                        if (chipTagsName) chipTagsName.textContent = 'With: ' + val;
                        if (chipTags) chipTags.hidden = false;
                        if (tagPopover) tagPopover.hidden = true;
                        updateChipsVisibility();
                    }
                }
            });

            removeTagsBtn?.addEventListener('click', (e) => {
                e.preventDefault();
                if (taggedInput) taggedInput.value = '';
                if (chipTags) chipTags.hidden = true;
                updateChipsVisibility();
            });

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

            // Close composer popovers on click outside
            document.addEventListener('click', (e) => {
                if (!e.target.closest('.sp-action-dropdown-wrap')) {
                    closeAllComposerPopovers();
                }
                if (!e.target.closest('.sp-feed-sort-dropdown-wrap')) {
                    const sortPop = document.getElementById('spFeedSortPopover');
                    if (sortPop) sortPop.hidden = true;
                }
                if (!e.target.closest('.sp-post-menu-wrap')) {
                    document.querySelectorAll('.sp-post-options-menu').forEach((m) => (m.hidden = true));
                }
            });

            // Feed Sort Dropdown Toggle
            const feedSortBtn = document.getElementById('spFeedSortBtn');
            const feedSortPopover = document.getElementById('spFeedSortPopover');
            const feedSortLabel = document.getElementById('spFeedSortLabel');

            feedSortBtn?.addEventListener('click', (e) => {
                e.stopPropagation();
                if (feedSortPopover) feedSortPopover.hidden = !feedSortPopover.hidden;
            });

            document.querySelectorAll('.sp-sort-opt').forEach((btn) => {
                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    document.querySelectorAll('.sp-sort-opt').forEach((b) => b.classList.remove('is-active'));
                    btn.classList.add('is-active');
                    if (feedSortLabel) feedSortLabel.textContent = btn.textContent.trim();
                    if (feedSortPopover) feedSortPopover.hidden = true;
                });
            });

            // -------------------------------------------------------------
            // Hold / Hover Reaction Picker (Facebook Style)
            // -------------------------------------------------------------
            const closeAllReactionDocks = () => {
                document.querySelectorAll('.sp-reactions-dock').forEach((d) => (d.hidden = true));
            };

            let holdTimer = null;
            let hoverTimer = null;

            // Handle Desktop Hover on Like Action Wrap
            document.querySelectorAll('.sp-like-action-wrap').forEach((wrap) => {
                const dock = wrap.querySelector('.sp-reactions-dock');

                wrap.addEventListener('mouseenter', () => {
                    clearTimeout(hoverTimer);
                    hoverTimer = setTimeout(() => {
                        closeAllReactionDocks();
                        if (dock) dock.hidden = false;
                    }, 350);
                });

                wrap.addEventListener('mouseleave', () => {
                    clearTimeout(hoverTimer);
                    hoverTimer = setTimeout(() => {
                        if (dock) dock.hidden = true;
                    }, 250);
                });
            });

            // Handle Hold / Long-press on Like Button (Touch & Mouse)
            document.addEventListener('mousedown', (e) => {
                const likeBtn = e.target.closest('.community-like-btn');
                if (likeBtn) {
                    const wrap = likeBtn.closest('.sp-like-action-wrap');
                    const dock = wrap?.querySelector('.sp-reactions-dock');
                    clearTimeout(holdTimer);
                    holdTimer = setTimeout(() => {
                        closeAllReactionDocks();
                        if (dock) dock.hidden = false;
                    }, 320);
                }
            });

            document.addEventListener('mouseup', () => {
                clearTimeout(holdTimer);
            });

            document.addEventListener('touchstart', (e) => {
                const likeBtn = e.target.closest('.community-like-btn');
                if (likeBtn) {
                    const wrap = likeBtn.closest('.sp-like-action-wrap');
                    const dock = wrap?.querySelector('.sp-reactions-dock');
                    clearTimeout(holdTimer);
                    holdTimer = setTimeout(() => {
                        closeAllReactionDocks();
                        if (dock) dock.hidden = false;
                    }, 300);
                }
            }, { passive: true });

            document.addEventListener('touchend', () => {
                clearTimeout(holdTimer);
            });

            // Handle Clicking Individual Reaction Emojis
            document.addEventListener('click', (e) => {
                const dockReaction = e.target.closest('.sp-dock-reaction');
                if (dockReaction) {
                    e.stopPropagation();
                    const wrap = dockReaction.closest('.sp-like-action-wrap');
                    const dock = wrap?.querySelector('.sp-reactions-dock');
                    const likeBtn = wrap?.querySelector('.community-like-btn');
                    const iconEl = likeBtn?.querySelector('i');
                    const labelEl = likeBtn?.querySelector('.sp-like-label');

                    const label = dockReaction.dataset.label || 'Like';
                    const icon = dockReaction.dataset.icon || 'bi-hand-thumbs-up-fill';
                    const color = dockReaction.dataset.color || '#1877f2';

                    if (likeBtn) {
                        likeBtn.classList.add('is-liked');
                        if (labelEl) labelEl.textContent = label;
                        if (iconEl) {
                            iconEl.className = `bi ${icon} sp-action-love-icon`;
                            iconEl.style.color = color;
                        }

                        // Send AJAX request if like URL exists
                        const likeUrl = likeBtn.dataset.likeUrl;
                        if (likeUrl && !likeBtn.dataset.alreadySent) {
                            fetch(likeUrl, {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}',
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            }).catch(() => {});
                        }
                    }

                    if (dock) dock.hidden = true;
                    return;
                }

                // Handle standard tap / click on Like Button (Toggle)
                const likeBtn = e.target.closest('.community-like-btn');
                if (likeBtn) {
                    e.stopPropagation();
                    const iconEl = likeBtn.querySelector('i');
                    const labelEl = likeBtn.querySelector('.sp-like-label');
                    const isCurrentlyLiked = likeBtn.classList.contains('is-liked');

                    likeBtn.classList.toggle('is-liked', !isCurrentlyLiked);

                    if (!isCurrentlyLiked) {
                        if (labelEl) labelEl.textContent = 'Love';
                        if (iconEl) {
                            iconEl.className = 'bi bi-heart-fill sp-action-love-icon';
                            iconEl.style.color = '#e11d48';
                        }
                    } else {
                        if (labelEl) labelEl.textContent = 'Like';
                        if (iconEl) {
                            iconEl.className = 'bi bi-hand-thumbs-up sp-action-love-icon';
                            iconEl.style.color = '';
                        }
                    }

                    const likeUrl = likeBtn.dataset.likeUrl;
                    if (likeUrl) {
                        fetch(likeUrl, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}',
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        }).catch(() => {});
                    }
                }
            });

            // Close reactions docks on click outside
            document.addEventListener('click', (e) => {
                if (!e.target.closest('.sp-like-action-wrap')) {
                    closeAllReactionDocks();
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

            document.querySelectorAll('.community-like-btn').forEach((btn) => {
                btn.addEventListener('click', async () => {
                    const url = btn.dataset.likeUrl;
                    const card = btn.closest('.community-post');
                    if (!url || !card || btn.disabled) return;

                    btn.disabled = true;
                    try {
                        const data = await postJson(url);
                        btn.classList.toggle('is-liked', !!data.liked);
                        const labelEl = btn.querySelector('.sp-like-label');
                        if (labelEl) {
                            labelEl.textContent = data.liked ? 'Liked' : 'Like';
                        } else {
                            btn.textContent = data.liked ? 'Liked' : 'Like';
                        }
                        const likesEl = card.querySelector('[data-likes-count]');
                        if (likesEl) likesEl.textContent = `${data.likes_count} likes`;
                    } catch (e) {
                        alert('Could not update like. Try again.');
                    } finally {
                        btn.disabled = false;
                    }
                });
            });

            document.querySelectorAll('[data-comment-form]').forEach((form) => {
                form.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const card = form.closest('.community-post');
                    const input = form.querySelector('input[name="body"]');
                    const submitBtn = form.querySelector('button[type="submit"]');
                    if (!card || !input || !input.value.trim()) return;

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
                        if (countEl) countEl.textContent = `${data.comments_count} comments`;
                        input.value = '';
                    } catch (err) {
                        alert('Could not post comment. Try again.');
                    } finally {
                        submitBtn.disabled = false;
                    }
                });
            });

            // Table pagination (< 1 2 3 >) for activities (5 per page)
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
                                <button type="button" class="sp-page-btn sp-page-nav is-disabled" disabled aria-label="Previous page"><i class="bi bi-chevron-left"></i></button>
                                <button type="button" class="sp-page-btn sp-page-num is-active">1</button>
                                <button type="button" class="sp-page-btn sp-page-nav is-disabled" disabled aria-label="Next page"><i class="bi bi-chevron-right"></i></button>
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

                        // Prev button <
                        const prevBtn = document.createElement('button');
                        prevBtn.type = 'button';
                        prevBtn.className = 'sp-page-btn sp-page-nav' + (currentPage === 1 ? ' is-disabled' : '');
                        prevBtn.innerHTML = '<i class="bi bi-chevron-left"></i>';
                        prevBtn.title = 'Previous page';
                        prevBtn.disabled = currentPage === 1;
                        prevBtn.addEventListener('click', () => {
                            if (currentPage > 1) renderPage(currentPage - 1);
                        });
                        paginationContainer.appendChild(prevBtn);

                        // Page numbers: 1 2 3 ...
                        for (let i = 1; i <= totalPages; i++) {
                            const pageBtn = document.createElement('button');
                            pageBtn.type = 'button';
                            pageBtn.className = 'sp-page-btn sp-page-num' + (i === currentPage ? ' is-active' : '');
                            pageBtn.textContent = i;
                            pageBtn.addEventListener('click', () => {
                                renderPage(i);
                            });
                            paginationContainer.appendChild(pageBtn);
                        }

                        // Next button >
                        const nextBtn = document.createElement('button');
                        nextBtn.type = 'button';
                        nextBtn.className = 'sp-page-btn sp-page-nav' + (currentPage === totalPages ? ' is-disabled' : '');
                        nextBtn.innerHTML = '<i class="bi bi-chevron-right"></i>';
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
                        closeSidebar();
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

                    // Prev button <
                    const prevBtn = document.createElement('button');
                    prevBtn.type = 'button';
                    prevBtn.className = 'sp-page-btn sp-page-nav' + (currentListPage === 1 ? ' is-disabled' : '');
                    prevBtn.innerHTML = '<i class="bi bi-chevron-left"></i>';
                    prevBtn.title = 'Previous page';
                    prevBtn.disabled = currentListPage === 1;
                    prevBtn.addEventListener('click', () => {
                        if (currentListPage > 1) renderListPage(currentListPage - 1);
                    });
                    listPaginationEl.appendChild(prevBtn);

                    // Page number buttons: 1 2 3 ...
                    for (let i = 1; i <= totalPages; i++) {
                        const pageBtn = document.createElement('button');
                        pageBtn.type = 'button';
                        pageBtn.className = 'sp-page-btn sp-page-num' + (i === currentListPage ? ' is-active' : '');
                        pageBtn.textContent = i;
                        pageBtn.addEventListener('click', () => {
                            renderListPage(i);
                        });
                        listPaginationEl.appendChild(pageBtn);
                    }

                    // Next button >
                    const nextBtn = document.createElement('button');
                    nextBtn.type = 'button';
                    nextBtn.className = 'sp-page-btn sp-page-nav' + (currentListPage === totalPages ? ' is-disabled' : '');
                    nextBtn.innerHTML = '<i class="bi bi-chevron-right"></i>';
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
                            is_rsvp: false,
                            breakdown: [
                                { label: '1st & 2nd Year', count: 105, pct: 57 },
                                { label: '3rd & 4th Year', count: 80, pct: 43 }
                            ]
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
                const part = data.participants || { registered: 185, capacity: 250, target: 'All Students', is_rsvp: false, breakdown: [] };
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

                const demoBarsEl = document.getElementById('spActDemoBars');
                if (demoBarsEl) {
                    const breakdown = part.breakdown || [
                        { label: '1st & 2nd Year', count: Math.round(regCount * 0.55), pct: 55 },
                        { label: '3rd & 4th Year', count: Math.round(regCount * 0.45), pct: 45 }
                    ];

                    demoBarsEl.innerHTML = breakdown
                        .map((b) => `
                            <div class="sp-act-demo-row">
                                <div class="sp-demo-label-row">
                                    <span>${b.label}</span>
                                    <strong>${b.count} (${b.pct}%)</strong>
                                </div>
                                <div class="sp-demo-track">
                                    <div class="sp-demo-fill" style="width: ${b.pct}%;"></div>
                                </div>
                            </div>
                        `)
                        .join('');
                }

                // RSVP Button state
                const rsvpBtn = document.getElementById('spActRsvpBtn');
                const rsvpText = document.getElementById('spActRsvpBtnText');
                if (rsvpBtn && rsvpText) {
                    if (part.is_rsvp) {
                        rsvpBtn.classList.add('is-registered');
                        rsvpText.textContent = 'You are Registered';
                    } else {
                        rsvpBtn.classList.remove('is-registered');
                        rsvpText.textContent = 'RSVP for Event';
                    }
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

            // Interactive RSVP button
            const rsvpBtn = document.getElementById('spActRsvpBtn');
            if (rsvpBtn) {
                rsvpBtn.addEventListener('click', () => {
                    const data = actModalEl._actData;
                    if (!data) return;

                    const isNowRegistered = !data.participants.is_rsvp;
                    data.participants.is_rsvp = isNowRegistered;
                    data.participants.registered += isNowRegistered ? 1 : -1;

                    openActModal({ dataset: { actDetails: JSON.stringify(data) } });
                    switchModalTab('participants');

                    const toast = document.createElement('div');
                    toast.className = 'sp-floating-toast';
                    toast.innerHTML = `<i class="bi bi-check-circle-fill"></i> <span>${isNowRegistered ? 'RSVP Confirmed! You are registered for this event.' : 'Registration cancelled.'}</span>`;
                    document.body.appendChild(toast);
                    setTimeout(() => toast.classList.add('is-show'), 20);
                    setTimeout(() => {
                        toast.classList.remove('is-show');
                        setTimeout(() => toast.remove(), 300);
                    }, 3000);
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
