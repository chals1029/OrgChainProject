<?php

namespace App\VotingSystem;

use App\VotingSystem\Controllers\AdminAuthController;
use App\VotingSystem\Controllers\AdminController;
use App\VotingSystem\Controllers\HomeController;
use App\VotingSystem\Controllers\MediaController;
use App\VotingSystem\Controllers\VoterController;
use App\VotingSystem\Core\Router;
use App\VotingSystem\Core\SecurityGuard;

class Kernel
{
    /**
     * Boot the integrated voting module and dispatch the current request.
     * Uses native $_SERVER / $_SESSION (bridged from Laravel where needed).
     */
    public function handle(): void
    {
        // Helpers + config
        if (! function_exists('voting_config')) {
            require __DIR__.'/helpers.php';
        }

        // Mirror Laravel config into legacy global for any residual reads.
        $GLOBALS['voting_config'] = config('voting');

        // Ensure session is available (Laravel usually already started it).
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Keep voting CSRF token available.
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }

        date_default_timezone_set((string) voting_config('timezone', 'Asia/Manila'));

        SecurityGuard::enforce();

        $router = new Router();

        $router->get('/', [HomeController::class, 'index']);

        $router->get('/vote/verify', [VoterController::class, 'verify']);
        $router->post('/vote/verify', [VoterController::class, 'verify']);
        $router->get('/vote/ballot', [VoterController::class, 'ballot']);
        $router->post('/vote/send-code', [VoterController::class, 'sendBallotCode']);
        $router->post('/vote/submit', [VoterController::class, 'submit']);
        $router->get('/vote/receipt', [VoterController::class, 'receipt']);
        $router->get('/auth/google', [VoterController::class, 'googleRedirect']);
        $router->get('/auth/google/callback', [VoterController::class, 'googleCallback']);
        $router->get('/media/candidate', [MediaController::class, 'candidate']);

        $adminLoginPath = admin_login_path();
        $adminLogoutPath = admin_logout_path();
        $router->get($adminLoginPath, [AdminAuthController::class, 'login']);
        $router->post($adminLoginPath, [AdminAuthController::class, 'login']);
        $router->post($adminLogoutPath, [AdminAuthController::class, 'logout']);

        $canvassingDashboardPath = canvassing_dashboard_path();
        $canvassingTallyPath = canvassing_tally_path();
        $canvassingReportsPath = canvassing_reports_path();
        $router->get('/admin', [AdminController::class, 'dashboard']);
        $router->get('/admin/dashboard', [AdminController::class, 'dashboard']);
        $router->get($canvassingDashboardPath, [AdminController::class, 'canvassingDashboard']);
        $router->get($canvassingTallyPath, [AdminController::class, 'canvassing']);
        $router->get('/admin/candidates', [AdminController::class, 'candidates']);
        $router->get('/admin/election', [AdminController::class, 'electionSettings']);
        $router->post('/admin/election', [AdminController::class, 'updateElectionSettings']);
        $router->get('/admin/canvassing-account', [AdminController::class, 'canvassingAccount']);
        $router->post('/admin/canvassing-account', [AdminController::class, 'storeCanvassingAccount']);
        $router->post('/admin/canvassing-account/update', [AdminController::class, 'updateCanvassingAccount']);
        $router->post('/admin/canvassing-account/delete', [AdminController::class, 'deleteCanvassingAccount']);
        $router->get('/admin/ballot-content', [AdminController::class, 'ballotContent']);
        $router->post('/admin/ballot-content', [AdminController::class, 'updateBallotContent']);
        $router->post('/admin/candidates', [AdminController::class, 'storeCandidate']);
        $router->post('/admin/candidates/update', [AdminController::class, 'updateCandidate']);
        $router->post('/admin/candidates/delete', [AdminController::class, 'deleteCandidate']);
        $router->get('/admin/voters', [AdminController::class, 'voters']);
        $router->post('/admin/voters/import', [AdminController::class, 'importVoters']);
        $router->post('/admin/voters/add', [AdminController::class, 'addVoter']);
        $router->post('/admin/voters/update', [AdminController::class, 'updateVoter']);
        $router->post('/admin/voters/reset-vote', [AdminController::class, 'resetVoterVote']);
        $router->post('/admin/voters/reset-all-votes', [AdminController::class, 'resetAllVotes']);
        $router->post('/admin/voters/delete', [AdminController::class, 'deleteVoter']);
        $router->post('/admin/voters/delete-all', [AdminController::class, 'deleteAllVoters']);
        $router->get('/admin/reports', [AdminController::class, 'reports']);
        $router->get($canvassingReportsPath, [AdminController::class, 'canvassingReportsGate']);
        $router->post($canvassingReportsPath, [AdminController::class, 'canvassingReportsVerifyPin']);
        $router->get('/admin/security', [AdminController::class, 'security']);
        $router->get('/admin/chain-verify', [AdminController::class, 'chainVerify']);
        $router->post('/admin/chain-verify', [AdminController::class, 'chainVerify']);

        $router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', voting_current_path());

        // Controllers often render without exit(); end the request cleanly.
        exit;
    }
}
