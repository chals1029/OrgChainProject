<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ strtoupper($office->office_role) }} Portal | OrgChain</title>

    @fonts

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet">
        <link rel="stylesheet" href="{{ asset('css/landing.css') }}">
    @endif
</head>
<body class="landing-body portal-body">
    <div class="page-ambient" aria-hidden="true">
        <span class="blob blob-a"></span>
        <span class="blob blob-b"></span>
        <span class="blob blob-c"></span>
    </div>

    <header class="site-header portal-header">
        <div class="header-pill liquid-glass">
            <a href="{{ route('office.home') }}" class="brand" aria-label="OrgChain office home">
                <img src="{{ asset('Orgchain logo.png') }}" alt="OrgChain Logo" class="brand-logo-img">
            </a>
            <div class="header-actions" style="margin-left: auto;">
                <form method="post" action="{{ route('office.logout') }}" class="portal-logout-form">
                    @csrf
                    <button type="submit" class="btn btn-login btn-pill-action">Logout</button>
                </form>
            </div>
        </div>
    </header>

    <div class="portal-shell office-shell">
        <aside class="portal-profile liquid-glass">
            <div class="portal-avatar" aria-hidden="true">
                <span>{{ $office->initials() }}</span>
            </div>
            <div class="portal-profile-copy">
                <p class="portal-kicker">Office Profile · Read only</p>
                <h1>{{ $office->name }}</h1>
                <div class="portal-profile-meta">
                    <span>{{ strtoupper($office->office_role) }}</span>
                    <span>Step {{ $meta['step'] }} of 4</span>
                </div>
                <p class="portal-college">{{ $office->office_title }}</p>
                <p class="portal-email">{{ $office->email }}</p>
            </div>
        </aside>

        <main class="portal-main">
            <section class="portal-section">
                <div class="portal-section-head">
                    <h2>Your place on the chain</h2>
                    <p>SO → OSO → SDO → OVCAA — one immutable approval trail.</p>
                </div>

                <div class="office-chain-steps">
                    @foreach (['so' => 'SO', 'oso' => 'OSO', 'sdo' => 'SDO', 'ovcaa' => 'OVCAA'] as $key => $label)
                        <article class="office-step-card liquid-glass {{ $office->office_role === $key ? 'is-current' : '' }}">
                            <span class="office-step-num">0{{ $loop->iteration }}</span>
                            <strong>{{ $label }}</strong>
                            @if ($office->office_role === $key)
                                <em>You are here</em>
                            @endif
                        </article>
                    @endforeach
                </div>
            </section>

            <section class="portal-section">
                <div class="portal-section-head">
                    <h2>Desk responsibilities</h2>
                    <p>What {{ strtoupper($office->office_role) }} handles in OrgChain.</p>
                </div>
                <article class="portal-card liquid-glass">
                    <h3>{{ $office->roleLabel() }}</h3>
                    <p>{{ $meta['duty'] }}</p>
                    <div class="portal-money-row" style="margin-top: 0.75rem;">
                        <span>Next handoff</span>
                        <strong style="color: var(--color-bsu-red);">{{ $meta['next'] }}</strong>
                    </div>
                </article>
            </section>

            <section class="portal-section">
                <div class="portal-section-head">
                    <h2>Account</h2>
                    <p>Credentials are managed by OrgChain admins — not editable here.</p>
                </div>
                <div class="portal-list">
                    <article class="portal-list-item liquid-glass">
                        <div>
                            <h3>Office email</h3>
                            <p>{{ $office->email }}</p>
                        </div>
                    </article>
                    <article class="portal-list-item liquid-glass">
                        <div>
                            <h3>Assigned desk</h3>
                            <p>{{ $office->roleLabel() }}</p>
                        </div>
                    </article>
                </div>
            </section>
        </main>
    </div>
</body>
</html>
