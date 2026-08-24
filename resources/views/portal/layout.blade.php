<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Student Portal | OrgChain</title>

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
                        <em>{{ $student->year_level }} Year</em>
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
                    <i class="bi bi-house-fill"></i>
                    <span>Home</span>
                </button>
                <button type="button" class="sp-nav-link {{ ($tab ?? '') === 'community' ? 'is-active' : '' }}" data-portal-tab="community">
                    <i class="bi bi-people-fill"></i>
                    <span>Community</span>
                </button>
            </nav>

            <form method="post" action="{{ route('student.logout') }}" class="sp-logout">
                @csrf
                <button type="submit">
                    <i class="bi bi-box-arrow-left"></i>
                    <span>Logout</span>
                </button>
            </form>
        </aside>

        {{-- Main area --}}
        <div class="sp-main">
            <header class="sp-topbar">
                <button type="button" class="sp-menu-toggle" id="spMenuToggle" aria-label="Open menu" aria-expanded="false" aria-controls="spSidebar">
                    <i class="bi bi-list"></i>
                </button>
                <div class="sp-topbar-title">
                    <p class="sp-module-kicker">BatStateU Student Portal</p>
                    <h1 class="sp-page-title" id="spPageTitle">{{ ($tab ?? 'home') === 'community' ? 'Community' : 'Home' }}</h1>
                </div>
                <div class="sp-top-actions">
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
                community: @json(route('portal.community')),
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
                if (!['home', 'community'].includes(tab)) return;

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
                    pageTitleEl.textContent = tab === 'community' ? 'Community' : 'Home';
                }

                document.getElementById('portalMain')?.scrollTo({ top: 0, behavior: 'smooth' });
                window.scrollTo({ top: 0, behavior: 'smooth' });

                if (push && urls[tab]) {
                    history.pushState({ tab }, '', urls[tab]);
                }

                document.title = (tab === 'community' ? 'Community' : 'Home') + ' | OrgChain';

                closeSidebar();
            };

            document.querySelectorAll('[data-portal-tab]').forEach((el) => {
                el.addEventListener('click', (e) => {
                    const tab = el.getAttribute('data-portal-tab');
                    if (!tab || !urls[tab]) return;
                    if (el.tagName === 'BUTTON' || el.classList.contains('sp-nav-link')) {
                        e.preventDefault();
                        switchTab(tab, true);
                    }
                });
            });

            window.addEventListener('popstate', (e) => {
                const tab = e.state?.tab || (location.pathname.includes('/community') ? 'community' : 'home');
                switchTab(tab, false);
            });

            document.getElementById('communityPhotoInput')?.addEventListener('change', function () {
                const label = document.getElementById('communityPhotoLabel');
                if (!label) return;
                label.textContent = this.files?.[0]?.name || 'Add photo';
            });

            // Keep community tab after form posts that land on /portal/community
            if (document.body.dataset.portalTab === 'community') {
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

            // Close sidebar on Escape
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && sidebar?.classList.contains('is-open')) {
                    closeSidebar();
                }
            });
        })();
    </script>
</body>
</html>
