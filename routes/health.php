<?php

use App\Http\Controllers\SystemHealthController;
use App\Services\Health\SystemHealthSnapshot;
use Illuminate\Support\Facades\Route;

// Registered outside the web group: readiness must remain readable if sessions or databases fail.
Route::get('/health/services', [SystemHealthController::class, 'index']);
Route::get('/health/services/{service}', [SystemHealthController::class, 'show'])
    ->whereIn('service', SystemHealthSnapshot::SERVICES);
