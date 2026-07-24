<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentAuthController extends Controller
{
    public function login(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'sr_code' => ['required', 'string', 'regex:/^\d{2}-\d{5}$/'],
            'password' => ['required', 'string', 'min:6'],
        ], [
            'sr_code.required' => 'Please enter your SR Code.',
            'sr_code.regex' => 'SR Code must look like 21-12345.',
            'password.required' => 'Please enter your password.',
            'password.min' => 'Password must be at least 6 characters.',
        ]);

        $student = Student::query()
            ->where('sr_code', $validated['sr_code'])
            ->where('is_active', true)
            ->first();

        if (! $student || ! Auth::guard('student')->attempt([
            'sr_code' => $validated['sr_code'],
            'password' => $validated['password'],
            'is_active' => true,
        ], $request->boolean('remember'))) {
            return back()
                ->withInput($request->only('sr_code'))
                ->with('login', true)
                ->withErrors([
                    'sr_code' => 'These credentials do not match our records.',
                ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('portal.home'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('student')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
