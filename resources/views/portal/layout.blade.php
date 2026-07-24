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
</head>
<body class="landing-body portal-body" data-portal-tab="{{ $tab ?? 'home' }}">
    <div class="page-ambient" aria-hidden="true">
        <span class="blob blob-a"></span>
        <span class="blob blob-b"></span>
        <span class="blob blob-c"></span>
    </div>

    <header class="site-header portal-header" id="siteHeader">
        <div class="header-pill liquid-glass">
            <a href="{{ route('portal.home') }}" class="brand" aria-label="OrgChain portal home" data-portal-tab="home">
                <img src="{{ asset('Orgchain logo.png') }}" alt="OrgChain Logo" class="brand-logo-img">
            </a>

            <nav class="nav-desktop portal-header-tabs" aria-label="Portal" role="tablist">
                <button type="button" class="portal-header-tab {{ ($tab ?? '') === 'home' ? 'is-active' : '' }}" data-portal-tab="home" role="tab" aria-selected="{{ ($tab ?? '') === 'home' ? 'true' : 'false' }}">Home</button>
                <button type="button" class="portal-header-tab {{ ($tab ?? '') === 'community' ? 'is-active' : '' }}" data-portal-tab="community" role="tab" aria-selected="{{ ($tab ?? '') === 'community' ? 'true' : 'false' }}">Community</button>
            </nav>

            <div class="header-actions">
                <a href="{{ url('/voting-system') }}" class="btn btn-primary btn-pill-action">
                    <span>Voting</span>
                </a>
                <form method="post" action="{{ route('student.logout') }}" class="portal-logout-form">
                    @csrf
                    <button type="submit" class="btn btn-login btn-pill-action">Logout</button>
                </form>
                <button type="button" class="btn-menu" id="menuToggle" aria-label="Open menu" aria-expanded="false" aria-controls="mobileNav">
                    <span></span><span></span><span></span>
                </button>
            </div>
        </div>

        <nav class="nav-mobile liquid-glass" id="mobileNav" aria-label="Mobile" hidden>
            <button type="button" class="portal-header-tab {{ ($tab ?? '') === 'home' ? 'is-active' : '' }}" data-portal-tab="home">Home</button>
            <button type="button" class="portal-header-tab {{ ($tab ?? '') === 'community' ? 'is-active' : '' }}" data-portal-tab="community">Community</button>
            <a href="{{ url('/voting-system') }}" class="btn btn-primary btn-block nav-mobile-cta">Voting System</a>
            <form method="post" action="{{ route('student.logout') }}">
                @csrf
                <button type="submit" class="btn btn-login btn-block">Logout</button>
            </form>
        </nav>
    </header>

    <div class="portal-shell">
        <aside class="portal-profile liquid-glass">
            <div class="portal-avatar" aria-hidden="true">
                @if (!empty($student->avatar_path))
                    <img src="{{ asset('storage/'.$student->avatar_path) }}" alt="">
                @else
                    <span>{{ $student->initials() }}</span>
                @endif
            </div>
            <div class="portal-profile-copy">
                <p class="portal-kicker">Student Profile · Read only</p>
                <h1>{{ $student->name }}</h1>
                <div class="portal-profile-meta">
                    <span>{{ $student->sr_code }}</span>
                    @if ($student->year_level)
                        <span>{{ $student->year_level }}</span>
                    @endif
                </div>
                @if ($student->program)
                    <p class="portal-college">{{ $student->program }}</p>
                @endif
                @if ($student->college)
                    <p class="portal-email">{{ $student->college }}</p>
                @endif
            </div>
        </aside>

        <main class="portal-main" id="portalMain">
            @if (session('status'))
                <div class="portal-flash liquid-glass" role="status">{{ session('status') }}</div>
            @endif

            @yield('content')
        </main>
    </div>

    <script>
        (function () {
            const urls = {
                home: @json(route('portal.home')),
                community: @json(route('portal.community')),
            };

            const switchTab = (tab, push = true) => {
                if (!['home', 'community'].includes(tab)) return;

                document.body.dataset.portalTab = tab;
                document.querySelectorAll('.portal-panel').forEach((panel) => {
                    const active = panel.dataset.panel === tab;
                    panel.hidden = !active;
                    panel.classList.toggle('is-active', active);
                });

                document.querySelectorAll('.portal-header-tab').forEach((btn) => {
                    const active = btn.dataset.portalTab === tab;
                    btn.classList.toggle('is-active', active);
                    btn.setAttribute('aria-selected', active ? 'true' : 'false');
                });

                document.getElementById('portalMain')?.scrollTo({ top: 0, behavior: 'smooth' });
                window.scrollTo({ top: 0, behavior: 'smooth' });

                if (push && urls[tab]) {
                    history.pushState({ tab }, '', urls[tab]);
                }

                document.title = (tab === 'community' ? 'Community' : 'Home') + ' | OrgChain';

                const mobileNav = document.getElementById('mobileNav');
                if (mobileNav && !mobileNav.hasAttribute('hidden')) {
                    mobileNav.setAttribute('hidden', '');
                    document.getElementById('menuToggle')?.setAttribute('aria-expanded', 'false');
                }
            };

            document.querySelectorAll('[data-portal-tab]').forEach((el) => {
                el.addEventListener('click', (e) => {
                    const tab = el.getAttribute('data-portal-tab');
                    if (!tab || !urls[tab]) return;
                    if (el.tagName === 'BUTTON' || el.classList.contains('brand')) {
                        e.preventDefault();
                        switchTab(tab, true);
                    }
                });
            });

            window.addEventListener('popstate', (e) => {
                const tab = e.state?.tab || (location.pathname.includes('/community') ? 'community' : 'home');
                switchTab(tab, false);
            });

            document.getElementById('menuToggle')?.addEventListener('click', function () {
                const nav = document.getElementById('mobileNav');
                if (!nav) return;
                const open = nav.hasAttribute('hidden');
                if (open) nav.removeAttribute('hidden');
                else nav.setAttribute('hidden', '');
                this.setAttribute('aria-expanded', open ? 'true' : 'false');
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
                        btn.textContent = data.liked ? 'Liked' : 'Like';
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
                            row.className = 'community-comment';
                            row.innerHTML = `<strong></strong><span></span>`;
                            row.querySelector('strong').textContent = data.comment.student_name;
                            row.querySelector('span').textContent = data.comment.body;
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
        })();
    </script>
</body>
</html>
