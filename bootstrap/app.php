<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Voting module uses its own CSRF tokens in legacy forms.
        $middleware->validateCsrfTokens(except: [
            'voting-system/*',
        ]);

        $middleware->alias([
            'student.auth' => \App\Http\Middleware\EnsureStudentAuthenticated::class,
            'office.auth' => \App\Http\Middleware\EnsureOfficeAuthenticated::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
