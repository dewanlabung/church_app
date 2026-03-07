<?php

use Illuminate\Support\Facades\Route;
use Plugins\Timeline\Controllers\FeedController;

// Public feed (no auth needed)
Route::get('/feed/public', [FeedController::class, 'publicFeed']);

// Authenticated feed
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/feed', [FeedController::class, 'index']);
});
