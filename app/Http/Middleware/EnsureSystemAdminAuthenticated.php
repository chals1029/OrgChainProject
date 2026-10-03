<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureSystemAdminAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::guard('system_admin')->check()) {
            return redirect()->route('system-admin.login')
                ->with('status', 'Sign in with a system maintenance administrator account.');
        }

        $admin = Auth::guard('system_admin')->user();
        if (! $admin || ! $admin->is_active) {
            Auth::guard('system_admin')->logout();

            return redirect()->route('system-admin.login')
                ->withErrors(['email' => 'This system administrator account is inactive.']);
        }

        return $next($request);
    }
}
