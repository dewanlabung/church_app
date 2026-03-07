<?php

use Illuminate\Support\Facades\Route;
use Plugins\Post\Controllers\PostController;
use Plugins\Post\Controllers\SaveController;
use Plugins\Post\Controllers\ShareController;

// Public
Route::get('/posts/public',       [PostController::class, 'public']);
Route::get('/posts/featured',     [PostController::class, 'featured']);
Route::get('/posts/{post}',       [PostController::class, 'show']);
Route::post('/posts/{post}/view', [PostController::class, 'incrementView']);

Route::middleware('auth:sanctum')->group(function () {
    // CRUD
    Route::get('/posts',          [PostController::class, 'index']);
    Route::post('/posts',         [PostController::class, 'store']);
    Route::put('/posts/{post}',   [PostController::class, 'update']);
    Route::delete('/posts/{post}',[PostController::class, 'destroy']);

    // Post privacy toggle
    Route::patch('/posts/{post}/privacy', [PostController::class, 'updatePrivacy']);

    // Saves / bookmarks
    Route::post('/posts/{post}/save',   [SaveController::class, 'save']);
    Route::delete('/posts/{post}/save', [SaveController::class, 'unsave']);
    Route::get('/posts/saved',          [SaveController::class, 'index']);

    // Shares
    Route::post('/posts/{post}/share',  [ShareController::class, 'share']);
});
