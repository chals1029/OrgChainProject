<?php

namespace App\Http\Controllers;

use App\Models\OfficeUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
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
        $validated = $request->validate([
            'email' => [
                'required',
                'email',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail) {
                    if (! OfficeUser::allowsEmailForRole((string) $value, 'so')) {
                        $fail('Use a BatStateU email or a registered organization Gmail address.');
                    }
                },
            ],
            'password' => ['required', 'string', 'min:6'],
        ], [
            'email.required' => 'Please enter your account email.',
            'email.email' => 'Enter a valid email address.',
            'password.required' => 'Please enter your password.',
        ]);

        $email = strtolower(trim($validated['email']));

        $user = DB::transaction(function () use ($request, $validated, $email): ?OfficeUser {
            $user = OfficeUser::query()->where('email', $email)->where('is_active', true)->lockForUpdate()->first();
            if (! $user
                || ! OfficeUser::allowsEmailForRole($email, (string) $user->office_role)
                || (str_ends_with($email, '@gmail.com') && ! $user->student_organization_id)
                || ! Hash::check($validated['password'], $user->password)) {
                return null;
            }
            Auth::guard('office')->login($user, $request->boolean('remember'));
            $request->session()->regenerate();
            $user->bindCurrentSession($request);

            return $user;
        });

        if (! $user) {
            return back()->withInput($request->only('email'))->withErrors([
                'email' => 'These credentials do not match an authorized office account.',
            ]);
        }

        return $user->must_change_password
            ? redirect()->route('office.password.change')
            : redirect()->intended(route('office.home'));
    }

    public function showPasswordChange(): Response
    {
        return response()->view('office.change-password', ['office' => Auth::guard('office')->user()])
            ->header('Cache-Control', 'private, no-store');
    }

    public function completePasswordChange(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = DB::transaction(function () use ($request, $validated): OfficeUser {
            $user = OfficeUser::query()->lockForUpdate()->findOrFail(Auth::guard('office')->id());
            $state = $request->session()->get('office_auth_state', []);
            abort_unless($user->is_active && (int) ($state['version'] ?? -1) === (int) $user->auth_version, 401);
            if (! Hash::check($validated['current_password'], $user->password)) {
                throw ValidationException::withMessages(['current_password' => 'The current password is incorrect.']);
            }
            if (Hash::check($validated['new_password'], $user->password)) {
                throw ValidationException::withMessages(['new_password' => 'Choose a password different from your current temporary password.']);
            }
            $user->password = $validated['new_password'];
            $user->must_change_password = false;
            $user->save();

            return $user;
        });
        $user->bindCurrentSession($request);
        $request->session()->regenerate();

        return redirect()->route('office.home')->with('success', 'Your password has been changed.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('office')->logout();
        $request->session()->forget('office_auth_state');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
