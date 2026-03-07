<?php

use Illuminate\Support\Facades\Route;
use Plugins\Question\Controllers\QuestionController;

Route::get('/questions', [QuestionController::class, 'index']);
Route::get('/questions/{id}/answers', [QuestionController::class, 'answers']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/questions/{id}/answers', [QuestionController::class, 'addAnswer']);
    Route::post('/answers/{id}/vote', [QuestionController::class, 'vote']);
    Route::post('/questions/{questionId}/best-answer/{answerId}', [QuestionController::class, 'markBestAnswer']);
});
