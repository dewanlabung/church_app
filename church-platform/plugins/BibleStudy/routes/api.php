<?php

use Illuminate\Support\Facades\Route;
use Plugins\BibleStudy\Controllers\BibleStudyController;

Route::get('/bible-studies', [BibleStudyController::class, 'index']);
Route::get('/bible-studies/{id}', [BibleStudyController::class, 'show']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/bible-studies', [BibleStudyController::class, 'store']);
    Route::post('/bible-studies/{id}/join', [BibleStudyController::class, 'join']);
    Route::post('/bible-studies/{studyId}/sessions', [BibleStudyController::class, 'addSession']);
    Route::post('/bible-studies/sessions/{sessionId}/notes', [BibleStudyController::class, 'saveNote']);
});
