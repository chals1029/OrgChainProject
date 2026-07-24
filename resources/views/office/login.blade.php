<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>Office Access | OrgChain</title>

    @fonts

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet">
        <link rel="stylesheet" href="{{ asset('css/landing.css') }}">
    @endif
</head>
<body class="landing-body office-login-body">
    <div class="page-ambient" aria-hidden="true">
        <span class="blob blob-a"></span>
        <span class="blob blob-b"></span>
        <span class="blob blob-c"></span>
    </div>

    <main class="office-login-shell">
        <section class="office-login-card liquid-glass">
            <div class="login-brand">
                <img src="{{ asset('Orgchain logo.png') }}" alt="" class="login-brand-logo" width="48" height="48">
                <div class="modal-badge">
                    <svg class="ico" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    Restricted Office Access
                </div>
            </div>

            <h1>Chain Desk Sign In</h1>
            <p class="office-login-lead">Sign in with your official BatStateU office email.</p>

            <form class="login-form" method="POST" action="{{ url('/'.trim(config('orgchain.office_login_path'), '/')) }}">
                @csrf

                <div class="form-field">
                    <label for="email">BatStateU Email</label>
                    <div class="input-wrap">
                        <svg class="input-ico" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 4h16v16H4z"/><path d="m4 7 8 6 8-6"/></svg>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            placeholder="name@g.batstate-u.edu.ph"
                            autocomplete="username"
                            required
                            value="{{ old('email') }}"
                            autofocus
                        >
                    </div>
                    <span class="field-hint">Use the institutional email assigned to your office</span>
                </div>

                <div class="form-field">
                    <label for="office_password">Password</label>
                    <div class="password-wrap input-wrap">
                        <svg class="input-ico" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        <input
                            type="password"
                            id="office_password"
                            name="password"
                            placeholder="Enter your password"
                            autocomplete="current-password"
                            required
                            minlength="6"
                        >
                        <button type="button" class="toggle-password" id="toggleOfficePassword" aria-label="Show password">Show</button>
                    </div>
                </div>

                @if ($errors->any())
                    <div class="form-alert" role="alert">{{ $errors->first() }}</div>
                @endif

                <button type="submit" class="btn btn-primary btn-block btn-login-submit">
                    Authenticate
                </button>
            </form>
        </section>
    </main>

    <script>
        document.getElementById('toggleOfficePassword')?.addEventListener('click', function () {
            const input = document.getElementById('office_password');
            if (!input) return;
            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            this.textContent = show ? 'Hide' : 'Show';
            this.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
        });
    </script>
</body>
</html>
