<?php

use App\VotingSystem\Kernel as VotingKernel;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| OrgChain Voting System (Laravel-integrated module)
|--------------------------------------------------------------------------
*/

$runVoting = static function (): void {
    if (! isset($_SESSION) || ! is_array($_SESSION)) {
        $_SESSION = [];
    }

    foreach (session()->all() as $key => $value) {
        $_SESSION[$key] = $value;
    }

    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = (string) (csrf_token() ?: bin2hex(random_bytes(16)));
    }

    register_shutdown_function(static function (): void {
        if (! isset($_SESSION) || ! is_array($_SESSION)) {
            return;
        }

        try {
            foreach ($_SESSION as $key => $value) {
                session([$key => $value]);
            }
            session()->save();
        } catch (Throwable) {
            // ignore
        }
    });

    app(VotingKernel::class)->handle();
    exit;
};

// Root of the voting module
Route::any('/', $runVoting)->name('voting.home');

// Nested paths (auth/google, admin, vote, etc.)
Route::any('/{any}', $runVoting)
    ->where('any', '.*')
    ->name('voting.catch-all');
