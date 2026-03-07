<?php

use Illuminate\Support\Facades\Route;
use Plugins\ChurchPage\Controllers\ChurchPageController;
use Plugins\ChurchPage\Controllers\ChurchSeoController;
use Plugins\ChurchPage\Controllers\ChurchMemberController;

// Public
Route::get('/churches',             [ChurchPageController::class, 'directory']);
Route::get('/churches/{slug}',      [ChurchPageController::class, 'show']);
Route::post('/churches/{slug}/view',[ChurchPageController::class, 'incrementView']);
Route::get('/churches/{slug}/feed', [ChurchPageController::class, 'feed']);
Route::get('/churches/{slug}/events',[ChurchPageController::class, 'events']);

Route::middleware('auth:sanctum')->group(function () {
    // Follow/unfollow
    Route::post('/churches/{church}/follow',   [ChurchMemberController::class, 'follow']);
    Route::delete('/churches/{church}/follow', [ChurchMemberController::class, 'unfollow']);
    Route::get('/churches/{church}/followers', [ChurchMemberController::class, 'followers']);

    // Admin: manage church pages
    Route::middleware('role:super_admin,church_admin')->group(function () {
        Route::get('/admin/churches',             [ChurchPageController::class, 'index']);
        Route::post('/admin/churches',            [ChurchPageController::class, 'store']);
        Route::put('/admin/churches/{church}',    [ChurchPageController::class, 'update']);
        Route::delete('/admin/churches/{church}', [ChurchPageController::class, 'destroy']);
        Route::patch('/admin/churches/{church}/status',   [ChurchPageController::class, 'updateStatus']);
        Route::patch('/admin/churches/{church}/featured', [ChurchPageController::class, 'toggleFeatured']);
        Route::get('/admin/churches/my-church',  [ChurchPageController::class, 'myChurch']);
    });

    // SEO meta
    Route::get('/admin/churches/{church}/seo',  [ChurchSeoController::class, 'show']);
    Route::put('/admin/churches/{church}/seo',  [ChurchSeoController::class, 'update']);
});
