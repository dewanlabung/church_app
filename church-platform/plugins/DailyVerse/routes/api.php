<?php

use Illuminate\Support\Facades\Route;
use Plugins\DailyVerse\Controllers\DailyVerseController;

// Public
Route::get('/verses/today',   [DailyVerseController::class, 'today']);
Route::get('/verses/random',  [DailyVerseController::class, 'random']);

Route::middleware('auth:sanctum')->group(function () {
    Route::middleware('role:super_admin,church_admin')->group(function () {
        Route::get('/admin/verses',              [DailyVerseController::class, 'index']);
        Route::post('/admin/verses',             [DailyVerseController::class, 'store']);
        Route::put('/admin/verses/{verse}',      [DailyVerseController::class, 'update']);
        Route::delete('/admin/verses/{verse}',   [DailyVerseController::class, 'destroy']);

        // CSV import/export
        Route::post('/admin/verses/import-csv',  [DailyVerseController::class, 'importCsv']);
        Route::get('/admin/verses/sample-csv',   [DailyVerseController::class, 'sampleCsv']);
        Route::get('/admin/verses/export-csv',   [DailyVerseController::class, 'exportCsv']);
    });
});
