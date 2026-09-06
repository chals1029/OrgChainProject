<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>Office Access | OrgChain</title>
    <link rel="icon" type="image/png" href="{{ asset('Orgchain logo.png') }}">

    @fonts

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700,800" rel="stylesheet">
        <link rel="stylesheet" href="{{ asset('css/landing.css') }}">
    @endif
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        :root {
            --color-bsu-red: #8b1828;
            --color-bsu-red-dark: #62101c;
            --color-bsu-red-muted: #fdf0f2;
            --color-bsu-ink: #1a1618;
            --color-bsu-muted: #7a7074;
            --color-bsu-border: #f0e6e8;
        }

        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            font-family: 'Instrument Sans', system-ui, -apple-system, sans-serif;
            -webkit-font-smoothing: antialiased;
            color: var(--color-bsu-ink);
        }

        .office-login-body {
            min-height: 100vh;
            min-height: 100dvh;
            display: flex;
            align-items: center;
            justify-content: center;
            background:
                radial-gradient(1200px 600px at 10% -10%, rgba(196, 59, 82, 0.15), transparent 55%),
                radial-gradient(900px 500px at 100% 20%, rgba(155, 27, 48, 0.1), transparent 50%),
                linear-gradient(180deg, #ffffff 0%, #faf6f7 40%, #f7f1f2 100%);
            background-attachment: fixed;
            position: relative;
            overflow-x: hidden;
            padding: 1.5rem;
            box-sizing: border-box;
        }

        .office-login-shell {
            width: 100%;
            max-width: 440px;
            margin: auto;
            position: relative;
            z-index: 10;
        }

        .office-login-card {
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1.5px solid var(--color-bsu-border);
            border-radius: 24px;
            padding: 2.25rem 2rem;
            box-shadow: 0 20px 50px rgba(139, 24, 40, 0.08), 0 4px 16px rgba(0, 0, 0, 0.03);
            box-sizing: border-box;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .office-login-card:hover {
            box-shadow: 0 24px 60px rgba(139, 24, 40, 0.12), 0 6px 20px rgba(0, 0, 0, 0.04);
        }

        .login-brand {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.25rem;
        }

        .login-brand-logo {
            width: 44px;
            height: 44px;
            object-fit: contain;
        }

        .modal-badge {
            background: var(--color-bsu-red-muted);
            color: var(--color-bsu-red);
            border: 1px solid #f2dfe2;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            border-radius: 9999px;
            padding: 0.3rem 0.75rem;
            font-size: 0.72rem;
            font-weight: 800;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
        }

        .office-login-card h1 {
            margin: 0 0 0.4rem;
            font-size: 1.45rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            color: var(--color-bsu-ink);
        }

        .office-login-lead {
            margin: 0 0 1.5rem;
            color: var(--color-bsu-muted);
            font-size: 0.88rem;
            line-height: 1.5;
        }

        .login-form {
            display: flex;
            flex-direction: column;
            gap: 1.15rem;
        }

        .form-field {
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
        }

        .form-field label {
            color: var(--color-bsu-ink);
            font-size: 0.82rem;
            font-weight: 700;
        }

        .input-wrap {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-ico {
            color: var(--color-bsu-muted);
            pointer-events: none;
            position: absolute;
            left: 0.95rem;
            font-size: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .form-field input {
            box-sizing: border-box;
            width: 100%;
            font: inherit;
            color: var(--color-bsu-ink);
            background: #ffffff;
            border: 1.5px solid var(--color-bsu-border);
            border-radius: 12px;
            padding: 0.75rem 0.95rem 0.75rem 2.6rem;
            font-size: 0.9rem;
            font-weight: 600;
            outline: none;
            transition: all 0.18s ease;
        }

        .form-field input:focus {
            border-color: var(--color-bsu-red);
            box-shadow: 0 0 0 3.5px rgba(139, 24, 40, 0.08);
        }

        .password-wrap input {
            padding-right: 3.5rem;
        }

        .toggle-password {
            position: absolute;
            right: 0.85rem;
            background: transparent;
            border: none;
            color: var(--color-bsu-red);
            font-size: 0.78rem;
            font-weight: 700;
            cursor: pointer;
            padding: 0.25rem 0.4rem;
            border-radius: 6px;
            outline: none;
        }

        .toggle-password:hover {
            background: var(--color-bsu-red-muted);
        }

        .field-hint {
            font-size: 0.74rem;
            color: var(--color-bsu-muted);
            line-height: 1.4;
        }

        .form-alert {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #b91c1c;
            padding: 0.75rem 1rem;
            border-radius: 10px;
            font-size: 0.82rem;
            font-weight: 600;
        }

        .btn-login-submit {
            width: 100%;
            padding: 0.85rem 1.5rem;
            background: var(--color-bsu-red);
            color: #ffffff;
            font-size: 0.92rem;
            font-weight: 800;
            border: none;
            border-radius: 9999px;
            cursor: pointer;
            box-shadow: 0 4px 14px rgba(139, 24, 40, 0.28);
            transition: all 0.2s ease;
            margin-top: 0.35rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            font-family: inherit;
        }

        .btn-login-submit:hover {
            background: var(--color-bsu-red-dark);
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(139, 24, 40, 0.35);
        }

        .btn-login-submit:active {
            transform: translateY(0);
        }

        .login-back-link {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            margin-top: 1.35rem;
            font-size: 0.82rem;
            font-weight: 700;
            color: var(--color-bsu-muted);
            text-decoration: none;
            transition: color 0.15s ease;
        }

        .login-back-link:hover {
            color: var(--color-bsu-red);
        }
    </style>
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
                <img src="{{ asset('Orgchain logo.png') }}" alt="OrgChain Logo" class="login-brand-logo" width="44" height="44">
                <div class="modal-badge">
                    <i class="bi bi-shield-lock-fill"></i>
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
                        <i class="bi bi-envelope-fill input-ico"></i>
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
                        <i class="bi bi-lock-fill input-ico"></i>
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
                    <div class="form-alert" role="alert">
                        <i class="bi bi-exclamation-circle-fill"></i> {{ $errors->first() }}
                    </div>
                @endif

                <button type="submit" class="btn-login-submit">
                    <span>Authenticate</span>
                    <i class="bi bi-arrow-right"></i>
                </button>
            </form>

            <a href="{{ url('/') }}" class="login-back-link">
                <i class="bi bi-arrow-left"></i> Return to OrgChain Home
            </a>
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
