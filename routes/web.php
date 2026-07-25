<?php

use App\Http\Controllers\CommunityFeedController;
use App\Http\Controllers\OfficeAuthController;
use App\Http\Controllers\OfficePortalController;
use App\Http\Controllers\StudentAuthController;
use App\Http\Controllers\StudentPortalController;
use App\VotingSystem\Kernel as VotingKernel;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/student/login', [StudentAuthController::class, 'login'])->name('student.login');
Route::post('/student/logout', [StudentAuthController::class, 'logout'])->name('student.logout');

$officeLoginPath = '/'.trim((string) config('orgchain.office_login_path', '/orgchain-office-access-a9e2f71c4b83'), '/');
Route::match(['GET', 'POST'], $officeLoginPath, function (\Illuminate\Http\Request $request) {
    $controller = app(\App\Http\Controllers\OfficeAuthController::class);

    return $request->isMethod('post')
        ? $controller->login($request)
        : $controller->showLogin();
})->name('office.login');

Route::post('/office/logout', [OfficeAuthController::class, 'logout'])->name('office.logout');

Route::middleware('student.auth')->prefix('portal')->name('portal.')->group(function () {
    Route::get('/', [StudentPortalController::class, 'home'])->name('home');
    Route::get('/community', [StudentPortalController::class, 'community'])->name('community');

    Route::post('/community/posts', [CommunityFeedController::class, 'store'])->name('community.posts.store');
    Route::post('/community/posts/{post}/like', [CommunityFeedController::class, 'like'])->name('community.posts.like');
    Route::post('/community/posts/{post}/comments', [CommunityFeedController::class, 'comment'])->name('community.posts.comment');
    Route::delete('/community/posts/{post}', [CommunityFeedController::class, 'destroy'])->name('community.posts.destroy');
});

Route::middleware('office.auth')->prefix('office-desk')->name('office.')->group(function () {
    Route::get('/', [OfficePortalController::class, 'dashboard'])->name('home');
    Route::get('/analytics', [OfficePortalController::class, 'analytics'])->name('analytics');
    Route::get('/activities', [OfficePortalController::class, 'activities'])->name('activities');
    Route::get('/calendar', [OfficePortalController::class, 'calendar'])->name('calendar');
    Route::get('/budget-utilization', [OfficePortalController::class, 'budget'])->name('budget');
    Route::get('/financial-report', [OfficePortalController::class, 'financial'])->name('financial');
    Route::get('/accomplishment-report', [OfficePortalController::class, 'accomplishment'])->name('accomplishment');
});

/*
|--------------------------------------------------------------------------
| OrgChain Voting System (integrated under /voting-system)
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

// Explicit high-traffic paths (guaranteed match)
Route::match(['GET', 'POST'], '/voting-system/auth/google', $runVoting);
Route::match(['GET', 'POST'], '/voting-system/auth/google/callback', $runVoting);
Route::match(['GET', 'POST'], '/voting-system/vote/{segment}', $runVoting)->where('segment', '.*');
Route::match(['GET', 'POST'], '/voting-system/admin/{segment}', $runVoting)->where('segment', '.*');
Route::match(['GET', 'POST'], '/voting-system/media/{segment}', $runVoting)->where('segment', '.*');
Route::match(['GET', 'POST'], '/voting-system/ssc-{segment}', $runVoting)->where('segment', '.*');

// Module root + generic fallback
Route::match(['GET', 'POST'], '/voting-system', $runVoting)->name('voting.home');
Route::match(['GET', 'POST'], '/voting-system/{segment}', $runVoting)
    ->where('segment', '.*')
    ->name('voting.any');
