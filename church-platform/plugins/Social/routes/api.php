<?php

use Illuminate\Support\Facades\Route;
use Plugins\Social\Controllers\FeedController;
use Plugins\Social\Controllers\FollowController;
use Plugins\Social\Controllers\ProfileController;
use Plugins\Social\Controllers\SearchPeopleController;

Route::middleware('auth:sanctum')->group(function () {
    // Feed
    Route::get('/feed',          [FeedController::class, 'index']);
    Route::get('/feed/refresh',  [FeedController::class, 'refresh']);

    // Follow / Unfollow
    Route::post('/users/{user}/follow',   [FollowController::class, 'follow']);
    Route::delete('/users/{user}/follow', [FollowController::class, 'unfollow']);
    Route::get('/users/{user}/followers', [FollowController::class, 'followers']);
    Route::get('/users/{user}/following', [FollowController::class, 'following']);

    // Public profile
    Route::get('/users/{username}/profile', [ProfileController::class, 'show']);
    Route::get('/users/{username}/posts',   [ProfileController::class, 'posts']);
});
