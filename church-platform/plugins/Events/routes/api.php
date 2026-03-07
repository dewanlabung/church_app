<?php

use Illuminate\Support\Facades\Route;
use Plugins\Events\Controllers\EventController;

Route::get('/events', [EventController::class, 'index']);
Route::get('/events/{id}', [EventController::class, 'show']);
Route::get('/events/{id}/attendees', [EventController::class, 'attendees']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/events', [EventController::class, 'store']);
    Route::put('/events/{id}', [EventController::class, 'update']);
    Route::delete('/events/{id}', [EventController::class, 'destroy']);
    Route::post('/events/{id}/rsvp', [EventController::class, 'rsvp']);
});
