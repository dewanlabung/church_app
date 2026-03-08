<?php

use Illuminate\Support\Facades\Route;
use Plugins\Chat\Controllers\ChatController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/chat/conversations',       [ChatController::class, 'conversations']);
    Route::get('/chat/messages/{peerId}',   [ChatController::class, 'messages']);
    Route::post('/chat/messages/{peerId}',  [ChatController::class, 'send']);
    Route::delete('/chat/messages/{id}',    [ChatController::class, 'delete']);
});
