<?php

use Illuminate\Support\Facades\Route;
use Plugins\Greeting\Controllers\GreetingController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/greeting', [GreetingController::class, 'show']);

    Route::middleware('role:super_admin')->group(function () {
        Route::get('/admin/greeting/settings',  [GreetingController::class, 'adminSettings']);
        Route::put('/admin/greeting/settings',  [GreetingController::class, 'updateSettings']);
    });
});
