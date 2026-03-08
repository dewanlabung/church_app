<?php

use Illuminate\Support\Facades\Route;
use Plugins\Newsletter\Controllers\NewsletterController;

// Public
Route::post('/newsletter/subscribe',              [NewsletterController::class, 'subscribe']);
Route::get('/newsletter/unsubscribe/{token}',     [NewsletterController::class, 'unsubscribe']);

// Admin
Route::middleware(['auth:sanctum', 'role:super_admin,church_admin'])->prefix('admin')->group(function () {
    Route::get('/newsletter/subscribers',         [NewsletterController::class, 'subscribers']);
    Route::post('/newsletter/send',               [NewsletterController::class, 'send']);
    Route::delete('/newsletter/subscribers/{id}', [NewsletterController::class, 'destroy']);
});
