<?php

use Illuminate\Support\Facades\Route;
use Plugins\Ads\Controllers\AdsController;

// Public: get ad for a position
Route::get('/ads/{position}', [AdsController::class, 'show'])->middleware('throttle:120,1');

// Admin CRUD
Route::middleware(['auth:sanctum', 'role:super_admin'])->prefix('admin')->group(function () {
    Route::get('/ads',         [AdsController::class, 'index']);
    Route::post('/ads',        [AdsController::class, 'store']);
    Route::put('/ads/{id}',    [AdsController::class, 'update']);
    Route::delete('/ads/{id}', [AdsController::class, 'destroy']);
});
