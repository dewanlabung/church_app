<?php

use Illuminate\Support\Facades\Route;
use Plugins\Community\Controllers\CommunityController;
use Plugins\Community\Controllers\CommunityMemberController;
use Plugins\Community\Controllers\CommunityPostController;

// Public
Route::get('/communities',          [CommunityController::class, 'index']);
Route::get('/communities/{slug}',   [CommunityController::class, 'show']);

Route::middleware('auth:sanctum')->group(function () {
    // CRUD
    Route::post('/communities',              [CommunityController::class, 'store']);
    Route::put('/communities/{community}',   [CommunityController::class, 'update']);
    Route::delete('/communities/{community}',[CommunityController::class, 'destroy']);

    // Membership
    Route::post('/communities/{community}/join',    [CommunityMemberController::class, 'join']);
    Route::delete('/communities/{community}/leave', [CommunityMemberController::class, 'leave']);
    Route::get('/communities/{community}/members',  [CommunityMemberController::class, 'index']);
    Route::post('/communities/{community}/invite',  [CommunityMemberController::class, 'invite']);
    Route::patch('/communities/{community}/members/{user}/role', [CommunityMemberController::class, 'updateRole']);
    Route::delete('/communities/{community}/members/{user}',     [CommunityMemberController::class, 'remove']);

    // Join requests (private communities)
    Route::get('/communities/{community}/requests',          [CommunityMemberController::class, 'requests']);
    Route::patch('/communities/{community}/requests/{user}/approve', [CommunityMemberController::class, 'approve']);
    Route::patch('/communities/{community}/requests/{user}/deny',    [CommunityMemberController::class, 'deny']);

    // Community feed
    Route::get('/communities/{community}/feed',  [CommunityPostController::class, 'index']);
    Route::post('/communities/{community}/posts',[CommunityPostController::class, 'store']);
});
