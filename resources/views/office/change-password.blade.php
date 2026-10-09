<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Change Your Password | OrgChain</title>
    <link rel="icon" type="image/png" href="{{ asset('Orgchain logo.png') }}">
    @fonts
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700,800" rel="stylesheet">
        <link rel="stylesheet" href="{{ asset('css/landing.css') }}">
    @endif
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; color: #1a1618; font-family: 'Instrument Sans', system-ui, -apple-system, sans-serif; }
        .password-page { min-height: 100vh; min-height: 100dvh; display: grid; place-items: center; padding: 1.5rem; background: radial-gradient(1200px 600px at 10% -10%, rgba(196, 59, 82, 0.15), transparent 55%), linear-gradient(180deg, #fff, #f7f1f2); }
        .password-card { width: 100%; max-width: 480px; padding: 2rem; background: #fff; border: 1.5px solid #f0e6e8; border-radius: 24px; box-shadow: 0 20px 50px rgba(139, 24, 40, 0.08); }
        .password-brand { display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin-bottom: 1.25rem; }
        .password-brand img { width: 44px; height: 44px; object-fit: contain; }
        .password-badge { color: #8b1828; background: #fdf0f2; border: 1px solid #f2dfe2; border-radius: 999px; padding: 0.35rem 0.7rem; font-size: 0.72rem; font-weight: 800; }
        h1 { margin: 0 0 0.5rem; font-size: 1.45rem; font-weight: 800; }
        .password-lead, .password-help { color: #7a7074; font-size: 0.88rem; line-height: 1.55; }
        .password-identity { padding: 0.8rem; margin: 1rem 0; border: 1px solid #f0e6e8; border-radius: 10px; background: #fdfafb; overflow-wrap: anywhere; }
        .password-identity strong, .password-identity span { display: block; }
        .password-identity span { margin-top: 0.25rem; color: #7a7074; font-size: 0.8rem; }
        .password-form { display: grid; gap: 1rem; }
        .password-field { display: grid; gap: 0.35rem; }
        .password-field label { font-weight: 700; font-size: 0.82rem; }
        .password-field input { width: 100%; padding: 0.8rem 0.9rem; border: 1.5px solid #e3dadd; border-radius: 10px; background: #fff; color: #1a1618; font: inherit; }
        .password-field input[aria-invalid="true"] { border-color: #b42338; }
        .password-error { margin: 0; color: #8b1828; font-size: 0.8rem; }
        .password-errors { padding: 0.8rem 1rem; margin-bottom: 1rem; border: 1px solid #e7a8b2; border-radius: 10px; color: #8b1828; background: #fff1f3; font-size: 0.82rem; }
        .password-errors ul { margin: 0.5rem 0 0; padding-left: 1.2rem; }
        .password-submit, .password-logout { width: 100%; padding: 0.85rem; border-radius: 10px; font: inherit; font-size: 0.88rem; font-weight: 700; cursor: pointer; }
        .password-submit { border: 1px solid #8b1828; background: #8b1828; color: #fff; }
        .password-submit:hover { background: #62101c; }
        .password-logout { margin-top: 1rem; border: 1px solid #e3dadd; background: #fff; color: #8b1828; }
        :focus-visible { outline: 3px solid #8b1828; outline-offset: 3px; }
        .password-help { margin: 0; font-size: 0.78rem; }
        @media (max-width: 480px) { .password-page { padding: 0.75rem; } .password-card { padding: 1.25rem; border-radius: 18px; } }
    </style>
</head>
<body>
    <main class="password-page">
        <section class="password-card" aria-labelledby="passwordChangeTitle">
            <div class="password-brand">
                <img src="{{ asset('Orgchain logo.png') }}" alt="OrgChain">
                <span class="password-badge">Account security</span>
            </div>
            <h1 id="passwordChangeTitle">Choose your own password</h1>
            <p class="password-lead">Your account uses a temporary password. You must replace it before accessing any other system pages or actions. You can log out at any time.</p>
            <div class="password-identity">
                <strong>{{ $office->name }}</strong>
                <span>{{ $office->email }}</span>
            </div>
            @if ($errors->any())
                <div id="passwordChangeErrors" class="password-errors" role="alert" tabindex="-1" autofocus>
                    <strong>Your password was not changed.</strong>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <form id="officePasswordChangeForm" class="password-form" method="POST" action="{{ route('office.password.complete') }}">
                @csrf
                <div class="password-field">
                    <label for="current_password">Current temporary password</label>
                    <input id="current_password" name="current_password" type="password" required autocomplete="current-password" autocapitalize="none" autocorrect="off" spellcheck="false" @if (!$errors->any()) autofocus @endif aria-invalid="{{ $errors->has('current_password') ? 'true' : 'false' }}" @error('current_password') aria-describedby="currentPasswordError" @enderror>
                    @error('current_password') <p id="currentPasswordError" class="password-error">{{ $message }}</p> @enderror
                </div>
                <div class="password-field">
                    <label for="new_password">New password</label>
                    <input id="new_password" name="new_password" type="password" required minlength="8" autocomplete="new-password" autocapitalize="none" autocorrect="off" spellcheck="false" aria-invalid="{{ $errors->has('new_password') ? 'true' : 'false' }}" aria-describedby="newPasswordHelp{{ $errors->has('new_password') ? ' newPasswordError' : '' }}">
                    <p id="newPasswordHelp" class="password-help">Use at least 8 characters. Choose a password different from your current temporary password, and keep it private.</p>
                    @error('new_password') <p id="newPasswordError" class="password-error">{{ $message }}</p> @enderror
                </div>
                <div class="password-field">
                    <label for="new_password_confirmation">Confirm new password</label>
                    <input id="new_password_confirmation" name="new_password_confirmation" type="password" required minlength="8" autocomplete="new-password" autocapitalize="none" autocorrect="off" spellcheck="false" aria-invalid="{{ $errors->has('new_password_confirmation') ? 'true' : 'false' }}" @error('new_password_confirmation') aria-describedby="newPasswordConfirmationError" @enderror>
                    @error('new_password_confirmation') <p id="newPasswordConfirmationError" class="password-error">{{ $message }}</p> @enderror
                </div>
                <button class="password-submit" type="submit">Change Password &amp; Continue</button>
            </form>
            <form id="officePasswordLogoutForm" method="POST" action="{{ route('office.logout') }}">
                @csrf
                <button class="password-logout" type="submit">Log Out</button>
            </form>
        </section>
    </main>
    <script>
        // Do not retain entered credentials when leaving or restoring this page.
        function clearPasswordChangeFields() {
            document.querySelectorAll('#officePasswordChangeForm input[type="password"]').forEach(input => input.value = '');
        }
        window.addEventListener('pagehide', clearPasswordChangeFields);
        window.addEventListener('pageshow', event => {
            if (event.persisted) clearPasswordChangeFields();
        });
    </script>
</body>
</html>
