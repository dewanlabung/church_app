<?php

use Illuminate\Support\Facades\Route;
use Plugins\Comment\Controllers\CommentController;

Route::get('/posts/{post}/comments', [CommentController::class, 'index']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/posts/{post}/comments',      [CommentController::class, 'store']);
    Route::put('/comments/{comment}',          [CommentController::class, 'update']);
    Route::delete('/comments/{comment}',       [CommentController::class, 'destroy']);

    // Replies (2nd level)
    Route::get('/comments/{comment}/replies',  [CommentController::class, 'replies']);
    Route::post('/comments/{comment}/replies', [CommentController::class, 'reply']);
});
