<?php

use Illuminate\Support\Facades\Route;
use Plugins\Search\Controllers\SearchController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/search', SearchController::class);
});
