<?php

use Illuminate\Support\Facades\Route;
use Plugins\Gdpr\Controllers\GdprController;

Route::middleware('auth:sanctum')->group(function () {
    // User self-service
    Route::get('/gdpr/export',           [GdprController::class, 'export']);
    Route::post('/gdpr/delete-request',  [GdprController::class, 'requestDeletion']);
    Route::delete('/gdpr/delete-request',[GdprController::class, 'cancelDeletion']);

    // Admin
    Route::middleware('role:super_admin')->group(function () {
        Route::get('/admin/gdpr/deletions',           [GdprController::class, 'pendingDeletions']);
        Route::post('/admin/gdpr/deletions/{id}/process', [GdprController::class, 'processDeletion']);
    });
});
