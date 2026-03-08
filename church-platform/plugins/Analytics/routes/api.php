<?php

use Illuminate\Support\Facades\Route;
use Plugins\Analytics\Controllers\AnalyticsController;

// Public tracking endpoint
Route::post('/analytics/track', [AnalyticsController::class, 'track'])->middleware('throttle:60,1');

// Admin dashboard
Route::middleware(['auth:sanctum', 'role:super_admin,church_admin'])->group(function () {
    Route::get('/admin/analytics', [AnalyticsController::class, 'dashboard']);
});
