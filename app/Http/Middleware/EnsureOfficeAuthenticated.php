<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Recaller;
use App\Models\OfficeUser;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureOfficeAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        $guard = Auth::guard('office');
        $authenticated = $guard->user();
        $user = $authenticated ? OfficeUser::query()->find($authenticated->id) : null;
        $state = $request->session()->get('office_auth_state');
        $recaller = new Recaller((string) $request->cookie($guard->getRecallerName(), ''));
        $freshRemember = $user && $guard->viaRemember() && $recaller->valid()
            && (int) $recaller->id() === (int) $user->id
            && filled($user->getRememberToken())
            && hash_equals((string) $user->getRememberToken(), $recaller->token());

        // A pre-migration session is valid only until the first credential revocation.
        // Remember authentication has already checked the current stored token in the provider.
        $validVersion = $user && ($freshRemember
            || (is_array($state) && (int) ($state['id'] ?? 0) === (int) $user->id
                && (int) ($state['version'] ?? -1) === (int) $user->auth_version)
            || (($state === null || (int) ($state['id'] ?? 0) !== (int) $user->id) && (int) $user->auth_version === 0));

        if (! $user || ! $user->is_active || ! $validVersion) {
            $guard->logoutCurrentDevice();
            $request->session()->forget(['office_auth_state', 'tosa_unlock']);
            $message = 'Your office session expired or this account is inactive. Sign in again.';
            if ($request->expectsJson() || $request->is('office-desk/budget-utilization/receipts/validate-document')) {
                return response()->json(['ok' => false, 'message' => $message], 401);
            }

            return redirect()->route('office.login')->withErrors(['email' => $message]);
        }

        $guard->setUser($user);
        $request->session()->put('office_auth_state', ['id' => (int) $user->id, 'version' => (int) $user->auth_version]);

        if ($user->must_change_password && ! $request->routeIs('office.password.change', 'office.password.complete', 'office.logout')) {
            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Change your temporary password before using the office desk.',
                    'password_change_url' => route('office.password.change'),
                ], 423);
            }

            return redirect()->route('office.password.change');
        }

        return $next($request);
    }
}
