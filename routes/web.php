<?php

use App\Http\Controllers\CommunityFeedController;
use App\Http\Controllers\OfficeAuthController;
use App\Http\Controllers\OfficePortalController;
use App\Http\Controllers\StudentAuthController;
use App\Http\Controllers\StudentPortalController;
use App\Http\Controllers\SystemAdminAuthController;
use App\Http\Controllers\SystemAdminController;
use App\VotingSystem\Kernel as VotingKernel;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/student/login/code', [StudentAuthController::class, 'sendCode'])->name('student.code.send');
Route::post('/student/login/verify', [StudentAuthController::class, 'verifyCode'])->name('student.code.verify');
Route::post('/student/logout', [StudentAuthController::class, 'logout'])->name('student.logout');
Route::get('/student/auth/google', [StudentAuthController::class, 'redirectToGoogle'])->name('student.auth.google');
Route::get('/student/auth/google/callback', [StudentAuthController::class, 'handleGoogleCallback'])->name('student.auth.google.callback');

$officeLoginPath = '/'.trim((string) config('orgchain.office_login_path', '/orgchain-office-access-a9e2f71c4b83'), '/');
Route::match(['GET', 'POST'], $officeLoginPath, function (\Illuminate\Http\Request $request) {
    $controller = app(\App\Http\Controllers\OfficeAuthController::class);

    return $request->isMethod('post')
        ? $controller->login($request)
        : $controller->showLogin();
})->name('office.login');

Route::post('/office/logout', [OfficeAuthController::class, 'logout'])->name('office.logout');

$systemAdminLoginPath = '/'.trim((string) config('orgchain.system_admin_login_path', '/system-admin/login'), '/');
Route::match(['GET', 'POST'], $systemAdminLoginPath, function (\Illuminate\Http\Request $request) {
    $controller = app(SystemAdminAuthController::class);

    return $request->isMethod('post')
        ? $controller->login($request)
        : $controller->showLogin();
})->name('system-admin.login');

Route::middleware('system_admin.auth')->prefix('system-admin')->name('system-admin.')->group(function () {
    Route::get('/', [SystemAdminController::class, 'dashboard'])->name('dashboard');
    Route::post('/logout', [SystemAdminAuthController::class, 'logout'])->name('logout');
    Route::patch('/office-users/{user}/status', [SystemAdminController::class, 'updateOfficeUserStatus'])->name('office-users.status');
    Route::post('/cache/clear', [SystemAdminController::class, 'clearCaches'])->name('cache.clear');
});

Route::middleware('student.auth')->prefix('portal')->name('portal.')->group(function () {
    Route::get('/', [StudentPortalController::class, 'home'])->name('home');
    Route::get('/community', [StudentPortalController::class, 'community'])->name('community');

    Route::post('/community/posts', [CommunityFeedController::class, 'store'])->name('community.posts.store');
    Route::get('/community/posts/{post}/likers', [CommunityFeedController::class, 'likers'])->name('community.posts.likers');
    Route::post('/community/posts/{post}/like', [CommunityFeedController::class, 'like'])->name('community.posts.like');
    Route::post('/community/posts/{post}/comments', [CommunityFeedController::class, 'comment'])->name('community.posts.comment');
    Route::post('/community/comments/{comment}/like', [CommunityFeedController::class, 'likeComment'])->name('community.comments.like');
    Route::delete('/community/posts/{post}', [CommunityFeedController::class, 'destroy'])->name('community.posts.destroy');
    Route::put('/profile', [StudentPortalController::class, 'updateProfile'])->name('profile.update');
    Route::post('/feedback', [StudentPortalController::class, 'storeFeedback'])->name('feedback.store');
    Route::post('/activities/{activity}/rsvp', [StudentPortalController::class, 'toggleRsvp'])->name('activities.rsvp');
    Route::get('/expenses/{receipt}/verify', [StudentPortalController::class, 'verifyExpenseSeal'])->name('expenses.verify');
    Route::get('/tosa/template', [StudentPortalController::class, 'downloadTosaTemplate'])->name('tosa.template');
    Route::post('/tosa/documents', [StudentPortalController::class, 'uploadTosaDocument'])->name('tosa.documents.store');
    Route::delete('/tosa/documents/{key}', [StudentPortalController::class, 'removeTosaDocument'])->name('tosa.documents.destroy');
    Route::post('/tosa/submit', [StudentPortalController::class, 'submitTosaApplication'])->name('tosa.submit');
});

Route::middleware('office.auth')->prefix('office-desk')->name('office.')->group(function () {
    Route::get('/', [OfficePortalController::class, 'dashboard'])->name('home');
    Route::get('/analytics', [OfficePortalController::class, 'analytics'])->name('analytics');
    Route::get('/analytics/export', [OfficePortalController::class, 'exportAnalytics'])->name('analytics.export');
    Route::get('/activities', [OfficePortalController::class, 'activities'])->name('activities');
    Route::get('/activities/create', [OfficePortalController::class, 'createActivity'])->name('activities.create');
    Route::get('/activities/templates/download', [OfficePortalController::class, 'downloadActivityTemplates'])->name('activities.templates.download');
    Route::post('/activities', [OfficePortalController::class, 'storeActivity'])->name('activities.store');
    Route::get('/activities/{submission}/edit', [OfficePortalController::class, 'editActivity'])->name('activities.edit');
    Route::put('/activities/{submission}', [OfficePortalController::class, 'updateActivity'])->name('activities.update');
    Route::delete('/activities/{submission}/attachments/{key}', [OfficePortalController::class, 'deleteAttachment'])->name('activities.attachments.destroy');
    Route::get('/activities/{submission}/attachments/{key}', [OfficePortalController::class, 'viewAttachment'])->name('activities.attachments.file');
    Route::post('/activities/{activity}/advance', [OfficePortalController::class, 'advanceActivity'])->name('activities.advance');
    Route::post('/activities/{activity}/return', [OfficePortalController::class, 'returnActivity'])->name('activities.return');
    Route::post('/activities/{activity}/docs/{doc}', [OfficePortalController::class, 'updateComplianceDoc'])->name('activities.docs.update');
    Route::post('/funds/{account}', [OfficePortalController::class, 'updateFunds'])->name('funds.update');
    Route::post('/reports/{report}/status', [OfficePortalController::class, 'updateReportStatus'])->name('reports.status');
    Route::post('/reports/{reportType}/documents', [OfficePortalController::class, 'storeReportDocument'])->name('reports.documents.store');
    Route::get('/reports/documents/{document}/view', [OfficePortalController::class, 'viewReportDocument'])->name('reports.documents.view');
    Route::post('/reports/semester/submit', [OfficePortalController::class, 'submitSemesterReports'])->name('reports.semester.submit');
    Route::post('/reports/semester/review', [OfficePortalController::class, 'reviewSemesterReports'])->name('reports.semester.review');
    Route::get('/budget-utilization/print', [OfficePortalController::class, 'printBudget'])->name('budget.print');
    Route::get('/financial-report/print', [OfficePortalController::class, 'printFinancial'])->name('financial.print');
    Route::get('/accomplishment-report/print', [OfficePortalController::class, 'printAccomplishment'])->name('accomplishment.print');
    Route::post('/oso/remind/{activity}', [OfficePortalController::class, 'sendOrgReminder'])->name('oso.remind');
    Route::get('/calendar', [OfficePortalController::class, 'calendar'])->name('calendar');
    Route::get('/budget-utilization', [OfficePortalController::class, 'budget'])->name('budget');
    Route::get('/budget-utilization/receipts/{review}/view', [OfficePortalController::class, 'viewReceipt'])->name('budget.receipts.view');
    Route::post('/budget-utilization/receipts/validate-document', \App\Http\Controllers\ReceiptDocumentValidationController::class)->middleware('throttle:20,1')->name('budget.receipts.validate-document');
    Route::post('/budget-utilization/receipt-reviews', [OfficePortalController::class, 'storeReceiptReview'])->name('budget.receipts.store');
    Route::post('/budget-utilization/receipts/{review}/retry', [OfficePortalController::class, 'retryReceiptSeal'])->name('budget.receipts.retry');
    Route::get('/budget-utilization/receipt-package', \App\Http\Controllers\ReceiptPackageController::class)->name('budget.receipts.package');
    Route::post('/budget-utilization/accounts', [OfficePortalController::class, 'storeFundAccount'])->name('budget.accounts.store');
    Route::get('/financial-report', [OfficePortalController::class, 'financial'])->name('financial');
    Route::get('/accomplishment-report', [OfficePortalController::class, 'accomplishment'])->name('accomplishment');
    Route::get('/updates', [OfficePortalController::class, 'updates'])->name('updates');
    Route::post('/updates/announcements', [OfficePortalController::class, 'storeAnnouncement'])->name('updates.announcements.store');
    Route::get('/updates/announcements/{announcement}/attachment', [OfficePortalController::class, 'downloadAnnouncementAttachment'])->name('updates.announcements.attachment');
    Route::post('/updates/templates', [OfficePortalController::class, 'storeTemplate'])->name('updates.templates.store');
    Route::get('/updates/templates/{template}/download', [OfficePortalController::class, 'downloadTemplate'])->name('updates.templates.download');
    Route::get('/updates/templates/{id}/document', [OfficePortalController::class, 'downloadTemplateDocument'])->name('updates.templates.document');
    Route::get('/updates/announcements/{id}/document', [OfficePortalController::class, 'downloadAnnouncementDocument'])->name('updates.announcements.document');
    Route::post('/settings', [OfficePortalController::class, 'updateOsoSettings'])->name('settings.update');
    Route::post('/settings/account', [OfficePortalController::class, 'updateOsoAccount'])->name('settings.account');
    Route::post('/settings/password', [OfficePortalController::class, 'updateOsoPassword'])->name('settings.password');
    Route::post('/settings/pin/{type?}', [OfficePortalController::class, 'updateOsoPin'])->name('settings.pin');
    Route::post('/settings/tosa-pin/verify', [OfficePortalController::class, 'verifyOsoTosaPin'])->name('tosa.pin.verify');
    Route::post('/settings/logo', [OfficePortalController::class, 'storeOsoLogo'])->name('settings.logo');
    Route::delete('/settings/logo', [OfficePortalController::class, 'resetOsoLogo'])->name('settings.logo.reset');
    Route::post('/settings/users', [OfficePortalController::class, 'storeOsoUser'])->name('settings.users.store');
    Route::patch('/settings/users/{user}/status', [OfficePortalController::class, 'updateOsoUserStatus'])->name('settings.users.status');
    Route::get('/settings/snapshot', [OfficePortalController::class, 'downloadOsoSnapshot'])->name('settings.snapshot');
    Route::get('/settings/exports/{type}', [OfficePortalController::class, 'downloadOsoDataPackage'])->name('settings.exports');
    Route::get('/renewal', [OfficePortalController::class, 'renewal'])->name('renewal');
    Route::post('/renewal/window', [OfficePortalController::class, 'updateRenewalWindow'])->name('renewal.window');
    Route::post('/renewal/requirements/template', [OfficePortalController::class, 'storeRenewalRequirementTemplate'])->name('renewal.requirements.template');
    Route::post('/renewal/requirements', [OfficePortalController::class, 'storeRenewalRequirement'])->name('renewal.requirements.store');
    Route::patch('/renewal/requirements/{docKey}', [OfficePortalController::class, 'updateRenewalRequirement'])->name('renewal.requirements.update');
    Route::delete('/renewal/requirements/{docKey}', [OfficePortalController::class, 'destroyRenewalRequirement'])->name('renewal.requirements.destroy');
    Route::get('/renewal/requirements/{docKey}/file', [OfficePortalController::class, 'renewalRequirementFile'])->name('renewal.requirements.file');
    Route::post('/renewal/submit', [OfficePortalController::class, 'storeRenewalSubmission'])->name('renewal.submit');
    Route::post('/renewal/documents', [OfficePortalController::class, 'storeRenewalDocument'])->name('renewal.documents');
    Route::get('/renewal/submissions/{submission}', [OfficePortalController::class, 'showRenewalSubmission'])->name('renewal.submissions.show');
    Route::post('/renewal/submissions/{submission}/review', [OfficePortalController::class, 'reviewRenewalSubmission'])->name('renewal.review');
    Route::post('/renewal/documents/{document}/review', [OfficePortalController::class, 'reviewRenewalDocument'])->name('renewal.documents.review');
    Route::get('/renewal/documents/{document}/file', [OfficePortalController::class, 'renewalDocumentFile'])->name('renewal.documents.file');
    Route::post('/renewal/organizations/{organization}/status', [OfficePortalController::class, 'updateOrganizationQualification'])->name('renewal.organization.status');
    Route::get('/tosa', [OfficePortalController::class, 'tosa'])->name('tosa');
    Route::post('/tosa/requirements/template', [OfficePortalController::class, 'storeTosaRequirementTemplate'])->name('tosa.requirements.template');
    Route::post('/tosa/{applicant}/subsection', [OfficePortalController::class, 'updateTosaSubsection'])->name('tosa.subsection');
    Route::get('/archive', [OfficePortalController::class, 'archive'])->name('archive');
    Route::post('/archive/folders', [OfficePortalController::class, 'storeArchiveFolder'])->name('archive.folders.store');
    Route::post('/archive/documents', [OfficePortalController::class, 'storeArchiveDocument'])->name('archive.documents.store');
    Route::get('/student-reports', [OfficePortalController::class, 'studentReports'])->name('student-reports');
    Route::post('/student-reports/{report}/review', [OfficePortalController::class, 'reviewStudentReport'])->name('student-reports.review');
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
Route::match(['GET', 'POST'], '/voting-system/api/{segment}', $runVoting)->where('segment', '.*');
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
