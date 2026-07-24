<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureStudentAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::guard('student')->check()) {
            return redirect('/')
                ->with('login', true)
                ->with('status', 'Please sign in with your SR Code to open the student portal.');
        }

        $student = Auth::guard('student')->user();

        if (! $student || ! $student->is_active) {
            Auth::guard('student')->logout();

            return redirect('/')
                ->with('login', true)
                ->withErrors(['sr_code' => 'This student account is inactive.']);
        }

        return $next($request);
    }
}
