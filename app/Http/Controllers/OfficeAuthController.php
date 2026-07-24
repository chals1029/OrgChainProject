<?php

namespace App\Http\Controllers;

use App\Models\OfficeUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class OfficeAuthController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::guard('office')->check()) {
            return redirect()->route('office.home');
        }

        return view('office.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $allowedDomains = ['g.batstate-u.edu.ph', 'batstate-u.edu.ph'];

        $validated = $request->validate([
            'email' => [
                'required',
                'email',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail) use ($allowedDomains) {
                    $domain = strtolower((string) strrchr((string) $value, '@'));
                    $domain = ltrim($domain, '@');

                    if (! in_array($domain, $allowedDomains, true)) {
                        $fail('Use your official BatStateU email address.');
                    }
                },
            ],
            'password' => ['required', 'string', 'min:6'],
        ], [
            'email.required' => 'Please enter your BatStateU email.',
            'email.email' => 'Enter a valid email address.',
            'password.required' => 'Please enter your password.',
        ]);

        $email = strtolower(trim($validated['email']));

        $user = OfficeUser::query()
            ->where('email', $email)
            ->where('is_active', true)
            ->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors([
                    'email' => 'These credentials do not match an authorized office account.',
                ]);
        }

        Auth::guard('office')->login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended(route('office.home'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('office')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
