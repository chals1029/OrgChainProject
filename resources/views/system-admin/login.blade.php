<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>System Maintenance Admin | OrgChain</title>
    <link rel="icon" type="image/png" href="{{ asset('Orgchain logo.png') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700,800" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/system-admin.css') }}?v=2">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="sys-admin-login-body">
    <main class="sys-login-shell">
        <section class="sys-login-card" aria-labelledby="sysLoginTitle">
            <div class="sys-login-mark" aria-hidden="true"><i class="bi bi-shield-lock-fill"></i></div>
            <span class="sys-eyebrow">OrgChain control plane</span>
            <h1 id="sysLoginTitle">System Maintenance Admin</h1>
            <p class="sys-login-intro">Manage platform health, office access, and maintenance actions from a protected system console.</p>

            @if (session('status'))
                <div class="sys-alert sys-alert-info" role="status"><i class="bi bi-info-circle-fill"></i><span>{{ session('status') }}</span></div>
            @endif
            @if ($errors->any())
                <div class="sys-alert sys-alert-error" role="alert"><i class="bi bi-exclamation-triangle-fill"></i><span>{{ $errors->first() }}</span></div>
            @endif

            <form method="post" action="{{ route('system-admin.login') }}" class="sys-login-form">
                @csrf
                <label for="sysAdminEmail">Official administrator email</label>
                <div class="sys-input-wrap">
                    <i class="bi bi-person-badge" aria-hidden="true"></i>
                    <input id="sysAdminEmail" type="email" name="email" value="{{ old('email') }}" autocomplete="username" placeholder="system.admin@g.batstate-u.edu.ph" required autofocus>
                </div>

                <label for="sysAdminPassword">Password</label>
                <div class="sys-input-wrap">
                    <i class="bi bi-key" aria-hidden="true"></i>
                    <input id="sysAdminPassword" type="password" name="password" autocomplete="current-password" placeholder="Enter your password" required>
                </div>

                <label class="sys-check-row">
                    <input type="checkbox" name="remember" value="1">
                    <span>Keep me signed in on this device</span>
                </label>

                <button type="submit" class="sys-primary-btn">
                    <i class="bi bi-arrow-right-circle-fill" aria-hidden="true"></i> Open maintenance console
                </button>
            </form>

            <div class="sys-login-footer">
                <span><i class="bi bi-lock-fill" aria-hidden="true"></i> Restricted to authorized system administrators</span>
                <a href="{{ route('office.login') }}">Back to office sign-in</a>
            </div>
        </section>
    </main>
</body>
</html>
