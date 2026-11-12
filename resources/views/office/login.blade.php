<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>Office Access | OrgChain</title>
    <link rel="icon" type="image/png" href="{{ asset('orgchain-icon.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">

    @fonts

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700,800" rel="stylesheet">
    @endif
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        :root {
            --color-bsu-red: #8b1828;
            --color-bsu-red-bright: #aa1e33;
            --color-bsu-red-dark: #62101c;
            --color-bsu-red-muted: #fdf0f2;
            --color-bsu-red-border: #f2dfe2;
            --color-text-main: #1a1618;
            --color-text-muted: #7a7074;
            --color-text-subtle: #8c8287;
            --color-card-border: #f0e6e8;
            --color-input-bg: #fdfafb;
            --color-input-border: #ebd9dc;
        }

        *, *::before, *::after {
            box-sizing: border-box;
        }

        html, body {
            min-height: 100%;
            margin: 0;
            padding: 0;
            font-family: 'Instrument Sans', system-ui, -apple-system, sans-serif;
            -webkit-font-smoothing: antialiased;
            color: var(--color-text-main);
        }

        .office-login-body {
            min-height: 100vh;
            min-height: 100dvh;
            display: flex;
            align-items: center;
            justify-content: center;
            background:
                radial-gradient(1200px 600px at 10% -10%, rgba(196, 59, 82, 0.14), transparent 55%),
                radial-gradient(900px 500px at 100% 20%, rgba(155, 27, 48, 0.1), transparent 50%),
                linear-gradient(180deg, #ffffff 0%, #faf6f7 40%, #f7f1f2 100%);
            background-attachment: fixed;
            position: relative;
            padding: 1.5rem;
            overflow-x: hidden;
        }

        .office-login-shell {
            width: 100%;
            max-width: 1060px;
            margin: auto;
            position: relative;
            z-index: 10;
        }

        /* --- 2-COLUMN SPLIT CONTAINER (Layout inspired by reference image) --- */
        .office-login-container {
            display: grid;
            grid-template-columns: 1.05fr 1fr;
            background: #ffffff;
            border: 1.5px solid var(--color-card-border);
            border-radius: 28px;
            padding: 0.85rem;
            box-shadow: 0 25px 70px rgba(139, 24, 40, 0.08), 0 6px 24px rgba(0, 0, 0, 0.03);
            gap: 1.25rem;
            overflow: hidden;
        }

        /* --- LEFT COLUMN: CAMPUS HERO IMAGE PANEL --- */
        .office-login-hero {
            position: relative;
            min-height: 580px;
            border-radius: 22px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 1.75rem;
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.15);
        }

        .hero-slides-wrapper {
            position: absolute;
            inset: 0;
            z-index: 1;
            overflow: hidden;
        }

        .hero-slide {
            position: absolute;
            inset: 0;
            background-size: cover;
            background-position: center;
            opacity: 0;
            transform: translateX(100%);
            transition: transform 0.8s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.8s ease-in-out;
            pointer-events: none;
        }

        .hero-slide.is-active {
            opacity: 1;
            transform: translateX(0);
            z-index: 2;
        }

        .hero-slide.is-leaving {
            opacity: 0;
            transform: translateX(-100%);
            z-index: 1;
        }

        .hero-top-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: relative;
            z-index: 2;
        }

        .hero-brand {
            display: inline-flex;
            align-items: center;
            gap: 0.65rem;
            text-decoration: none;
            background: rgba(255, 255, 255, 0.94);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            padding: 0.35rem 0.85rem 0.35rem 0.45rem;
            border-radius: 9999px;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.15);
            transition: transform 0.15s ease;
        }

        .hero-brand:hover {
            transform: scale(1.02);
        }

        .hero-brand-logo {
            width: 32px;
            height: 32px;
            object-fit: contain;
        }

        .hero-brand-text {
            font-size: 0.95rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            color: var(--color-bsu-red);
        }

        .hero-back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.4);
            color: #ffffff;
            padding: 0.45rem 0.95rem;
            border-radius: 9999px;
            font-size: 0.8rem;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.2s ease;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.35);
        }

        .hero-back-link:hover {
            background: rgba(255, 255, 255, 0.32);
            border-color: rgba(255, 255, 255, 0.6);
            transform: translateX(2px);
        }

        .hero-bottom-content {
            position: relative;
            z-index: 2;
        }

        .hero-tag {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            background: rgba(139, 24, 40, 0.85);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: #ffffff;
            padding: 0.25rem 0.7rem;
            border-radius: 6px;
            font-size: 0.72rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.75rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.25);
        }

        .hero-title {
            margin: 0 0 0.45rem;
            font-size: 1.65rem;
            font-weight: 800;
            line-height: 1.25;
            letter-spacing: -0.02em;
            color: #ffffff;
            text-shadow: 0 2px 12px rgba(0, 0, 0, 0.6);
        }

        .hero-sub {
            margin: 0 0 1.25rem;
            font-size: 0.88rem;
            line-height: 1.5;
            color: #f8f5f6;
            text-shadow: 0 1px 6px rgba(0, 0, 0, 0.5);
            max-width: 420px;
        }

        .hero-slider-pills {
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .hero-pill-bar {
            height: 3.5px;
            padding: 0;
            border: 0;
            cursor: pointer;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.4);
            transition: all 0.2s ease;
        }

        .hero-pill-bar.is-active {
            width: 32px;
            background: #ffffff;
        }

        .hero-pill-bar.is-idle {
            width: 14px;
        }

        /* --- RIGHT COLUMN: WHITE THEME SIGN-IN FORM --- */
        .office-login-form-pane {
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 2.5rem 2.25rem;
        }

        .form-restricted-badge {
            align-self: flex-start;
            background: var(--color-bsu-red-muted);
            color: var(--color-bsu-red);
            border: 1px solid var(--color-bsu-red-border);
            letter-spacing: 0.04em;
            text-transform: uppercase;
            border-radius: 9999px;
            padding: 0.32rem 0.8rem;
            font-size: 0.72rem;
            font-weight: 800;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            margin-bottom: 1.25rem;
        }

        .office-login-form-pane h1 {
            margin: 0 0 0.45rem;
            font-size: 1.85rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            color: var(--color-text-main);
        }

        .office-login-lead {
            margin: 0 0 1.75rem;
            color: var(--color-text-muted);
            font-size: 0.88rem;
            line-height: 1.5;
        }

        .login-form {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }

        .form-field {
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
        }

        .form-field label {
            color: var(--color-text-main);
            font-size: 0.84rem;
            font-weight: 700;
        }

        .input-wrap {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-ico {
            color: var(--color-text-muted);
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
            color: var(--color-text-main);
            background: var(--color-input-bg);
            border: 1.5px solid var(--color-input-border);
            border-radius: 12px;
            padding: 0.8rem 0.95rem 0.8rem 2.65rem;
            font-size: 0.92rem;
            font-weight: 600;
            outline: none;
            transition: all 0.18s ease;
        }

        .form-field input::placeholder {
            color: #a3999e;
            font-weight: 400;
        }

        .form-field input:focus {
            border-color: var(--color-bsu-red);
            background: #ffffff;
            box-shadow: 0 0 0 3.5px rgba(139, 24, 40, 0.08);
        }

        .password-wrap input {
            padding-right: 3.8rem;
        }

        .toggle-password {
            position: absolute;
            right: 0.85rem;
            background: transparent;
            border: none;
            color: var(--color-text-muted);
            font-size: 1.15rem;
            cursor: pointer;
            padding: 0.25rem 0.4rem;
            border-radius: 6px;
            outline: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: color 0.15s ease;
        }

        .toggle-password:hover {
            color: var(--color-bsu-red);
        }

        .field-hint {
            font-size: 0.74rem;
            color: var(--color-text-muted);
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
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-login-submit {
            width: 100%;
            height: 48px;
            background: var(--color-bsu-red);
            color: #ffffff;
            font-size: 0.94rem;
            font-weight: 800;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            box-shadow: 0 4px 14px rgba(139, 24, 40, 0.28);
            transition: all 0.2s ease;
            margin-top: 0.35rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.55rem;
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
            margin-top: 1.5rem;
            font-size: 0.82rem;
            font-weight: 700;
            color: var(--color-text-muted);
            text-decoration: none;
            transition: color 0.15s ease;
        }

        .login-back-link:hover {
            color: var(--color-bsu-red);
        }

        /* --- RESPONSIVE / MOBILE VIEW --- */
        @media (max-width: 900px) {
            .office-login-container {
                grid-template-columns: 1fr;
                border-radius: 22px;
                padding: 0.75rem;
            }

            .office-login-hero {
                min-height: 240px;
                padding: 1.25rem;
            }

            .hero-title {
                font-size: 1.3rem;
            }

            .hero-sub {
                font-size: 0.8rem;
                margin-bottom: 0.75rem;
            }

            .office-login-form-pane {
                padding: 1.5rem 1rem 1.25rem;
            }

            .office-login-form-pane h1 {
                font-size: 1.55rem;
            }
        }
    </style>
</head>
<body class="landing-body office-login-body">
    <main class="office-login-shell">
        <div class="office-login-container">
            
            {{-- LEFT PANEL: BSU ARASOF CAMPUS HERO (Follows layout of reference image) --}}
            <section class="office-login-hero" aria-label="BatStateU ARASOF Campus Hero">
                <div class="hero-slides-wrapper" aria-hidden="true">
                    <div class="hero-slide is-active" style="background-image: linear-gradient(180deg, rgba(20, 10, 15, 0.55) 0%, rgba(20, 10, 15, 0.2) 40%, rgba(20, 10, 15, 0.88) 100%), url('{{ asset('images/bsuarasof.jpg') }}');"></div>
                    <div class="hero-slide" style="background-image: linear-gradient(180deg, rgba(20, 10, 15, 0.55) 0%, rgba(20, 10, 15, 0.2) 40%, rgba(20, 10, 15, 0.88) 100%), url('{{ asset('images/bsupic2-1.jpg') }}');"></div>
                    <div class="hero-slide" style="background-image: linear-gradient(180deg, rgba(20, 10, 15, 0.55) 0%, rgba(20, 10, 15, 0.2) 40%, rgba(20, 10, 15, 0.88) 100%), url('{{ asset('images/bsupic2-2.jpg') }}');"></div>
                </div>
                <div class="hero-top-row">
                    <a href="{{ url('/') }}" class="hero-brand" aria-label="OrgChain Home">
                        <img src="{{ asset('Orgchain logo.png') }}" alt="OrgChain Logo" class="hero-brand-logo">
                        <span class="hero-brand-text">OrgChain</span>
                    </a>
                </div>

                <div class="hero-bottom-content">
                    <div class="hero-tag">
                        <i class="bi bi-building"></i> The National Engineering University
                    </div>
                    <h2 class="hero-title">BatStateU ARASOF-Nasugbu</h2>
                    <p class="hero-sub">Empowering Recognized Student Organizations through Decentralized Governance and Accountable Operations.</p>
                    <div class="hero-slider-pills" aria-label="Campus slideshow">
                        <button type="button" class="hero-pill-bar is-active" data-slide-index="0" aria-label="Show campus image 1"></button>
                        <button type="button" class="hero-pill-bar is-idle" data-slide-index="1" aria-label="Show campus image 2"></button>
                        <button type="button" class="hero-pill-bar is-idle" data-slide-index="2" aria-label="Show campus image 3"></button>
                    </div>
                </div>
            </section>

            {{-- RIGHT PANEL: WHITE THEME SIGN-IN FORM --}}
            <section class="office-login-form-pane" aria-labelledby="loginHeading">
                <div class="form-restricted-badge">
                    <i class="bi bi-shield-lock-fill"></i>
                    Restricted Office Access
                </div>

                <h1 id="loginHeading">Chain Desk Sign In</h1>
                <p class="office-login-lead">Sign in with your registered office or organization email.</p>

                <form class="login-form" method="POST" action="{{ url('/'.trim(config('orgchain.office_login_path'), '/')) }}">
                    @csrf

                    <div class="form-field">
                        <label for="email">Email address</label>
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
                        <span class="field-hint">Offices use BatStateU email. Organizations may use their registered BatStateU or Gmail address.</span>
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
                            <button type="button" class="toggle-password" id="toggleOfficePassword" aria-label="Show password"><i class="bi bi-eye" id="togglePasswordIcon"></i></button>
                        </div>
                    </div>

                    @if ($errors->any())
                        <div class="form-alert error" role="alert">
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

        </div>
    </main>

    <script>
        document.getElementById('toggleOfficePassword')?.addEventListener('click', function () {
            const input = document.getElementById('office_password');
            const icon = document.getElementById('togglePasswordIcon') || this.querySelector('i');
            if (!input) return;
            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            if (icon) {
                icon.className = isPassword ? 'bi bi-eye-slash' : 'bi bi-eye';
            }
            this.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
        });

        (function () {
            const slides = document.querySelectorAll('.hero-slide');
            const pills = document.querySelectorAll('.hero-pill-bar');
            if (slides.length <= 1) return;

            let current = 0;
            let isTransitioning = false;
            const interval = 3000;

            function showSlide(nextIndex) {
                if (nextIndex === current || isTransitioning) return;
                isTransitioning = true;

                const prevSlide = slides[current];
                const nextSlide = slides[nextIndex];

                nextSlide.style.transition = 'none';
                nextSlide.classList.remove('is-leaving');
                void nextSlide.offsetHeight;
                nextSlide.style.transition = '';

                prevSlide.classList.remove('is-active');
                prevSlide.classList.add('is-leaving');
                nextSlide.classList.add('is-active');

                pills.forEach((pill, i) => {
                    pill.classList.toggle('is-active', i === nextIndex);
                    pill.classList.toggle('is-idle', i !== nextIndex);
                });
                current = nextIndex;

                setTimeout(() => {
                    prevSlide.style.transition = 'none';
                    prevSlide.classList.remove('is-leaving');
                    void prevSlide.offsetHeight;
                    prevSlide.style.transition = '';
                    isTransitioning = false;
                }, 850);
            }

            let timer = setInterval(() => {
                showSlide((current + 1) % slides.length);
            }, interval);

            pills.forEach((pill, i) => {
                pill.addEventListener('click', () => {
                    clearInterval(timer);
                    showSlide(i);
                    timer = setInterval(() => {
                        showSlide((current + 1) % slides.length);
                    }, interval);
                });
            });
        })();
    </script>
</body>
</html>
