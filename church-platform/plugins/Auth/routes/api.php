<?php

use Illuminate\Support\Facades\Route;
use Plugins\Auth\Controllers\AuthController;
use Plugins\Auth\Controllers\ForgotPasswordController;
use Plugins\Auth\Controllers\UserController;
use Plugins\Auth\Controllers\RoleController;

// Public
Route::post('/auth/register',       [AuthController::class, 'register']);
Route::post('/auth/login',          [AuthController::class, 'login']);
Route::post('/auth/forgot-password',[ForgotPasswordController::class, 'sendResetLink']);
Route::post('/auth/reset-password', [ForgotPasswordController::class, 'resetPassword']);
Route::get('/auth/verify-email/{token}', [AuthController::class, 'verifyEmail']);

// OAuth
Route::get('/auth/oauth/{provider}',          [AuthController::class, 'oauthRedirect']);
Route::get('/auth/oauth/{provider}/callback', [AuthController::class, 'oauthCallback']);

// Authenticated
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout',      [AuthController::class, 'logout']);
    Route::get('/auth/me',           [AuthController::class, 'me']);
    Route::put('/auth/profile',      [AuthController::class, 'updateProfile']);
    Route::post('/auth/change-password', [AuthController::class, 'changePassword']);
    Route::delete('/auth/account',   [AuthController::class, 'deleteAccount']);

    // Greeting
    Route::get('/auth/greeting',     [AuthController::class, 'greeting']);

    // Admin: User management
    Route::middleware('role:super_admin')->group(function () {
        Route::apiResource('users', UserController::class);
        Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword']);
        Route::post('/users/{user}/assign-role',    [RoleController::class, 'assignRole']);

        Route::apiResource('roles', RoleController::class);
    });
});
