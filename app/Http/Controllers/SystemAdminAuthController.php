<?php

namespace App\Http\Controllers;

use App\Models\SystemAdminAuditLog;
use App\Models\SystemAdminUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class SystemAdminAuthController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::guard('system_admin')->check()) {
            return redirect()->route('system-admin.dashboard');
        }

        return view('system-admin.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => [
                'required',
                'email',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $domain = strtolower((string) strrchr((string) $value, '@'));
                    $domain = ltrim($domain, '@');

                    if (! in_array($domain, ['g.batstate-u.edu.ph', 'batstate-u.edu.ph'], true)) {
                        $fail('Use an official BatStateU system administrator email address.');
                    }
                },
            ],
            'password' => ['required', 'string', 'min:8'],
        ], [
            'email.required' => 'Enter the system administrator email.',
            'password.required' => 'Enter the system administrator password.',
        ]);

        $email = strtolower(trim($validated['email']));
        $admin = SystemAdminUser::query()
            ->where('email', $email)
            ->where('is_active', true)
            ->first();

        if (! $admin || ! Hash::check($validated['password'], (string) $admin->password)) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'These credentials do not match an active system administrator account.']);
        }

        Auth::guard('system_admin')->login($admin, $request->boolean('remember'));
        $request->session()->regenerate();
        $admin->forceFill(['last_login_at' => now()])->save();
        SystemAdminAuditLog::record('login', 'system_admin:'.$admin->id);

        return redirect()->intended(route('system-admin.dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        $admin = Auth::guard('system_admin')->user();
        if ($admin) {
            SystemAdminAuditLog::record('logout', 'system_admin:'.$admin->id);
        }

        Auth::guard('system_admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('system-admin.login');
    }
}
