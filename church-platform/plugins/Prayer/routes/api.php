<?php

use Illuminate\Support\Facades\Route;
use Plugins\Prayer\Controllers\PrayerController;

Route::get('/prayer-wall', [PrayerController::class, 'wall']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/prayer/{id}/support', [PrayerController::class, 'support']);
    Route::post('/prayer/{id}/answered', [PrayerController::class, 'markAnswered']);
    Route::post('/prayer/{id}/respond', [PrayerController::class, 'privateRespond']);
});
