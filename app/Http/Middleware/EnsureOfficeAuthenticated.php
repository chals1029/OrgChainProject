<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureOfficeAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::guard('office')->check()) {
            $loginPath = '/'.trim((string) config('orgchain.office_login_path', '/orgchain-office-access-a9e2f71c4b83'), '/');

            return redirect($loginPath);
        }

        $user = Auth::guard('office')->user();

        if (! $user || ! $user->is_active) {
            Auth::guard('office')->logout();

            $loginPath = '/'.trim((string) config('orgchain.office_login_path', '/orgchain-office-access-a9e2f71c4b83'), '/');

            return redirect($loginPath)
                ->withErrors(['email' => 'This office account is inactive.']);
        }

        return $next($request);
    }
}
