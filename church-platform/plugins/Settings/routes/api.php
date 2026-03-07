<?php

use Illuminate\Support\Facades\Route;
use Plugins\Settings\Controllers\GeneralSettingsController;
use Plugins\Settings\Controllers\AppearanceController;
use Plugins\Settings\Controllers\ThemeController;
use Plugins\Settings\Controllers\MenuController;
use Plugins\Settings\Controllers\EmailSettingsController;
use Plugins\Settings\Controllers\StorageSettingsController;
use Plugins\Settings\Controllers\AuthSettingsController;
use Plugins\Settings\Controllers\SeoSettingsController;
use Plugins\Settings\Controllers\LocalizationController;
use Plugins\Settings\Controllers\PluginManagerController;
use Plugins\Settings\Controllers\SystemController;

// Public
Route::get('/settings/public',          [GeneralSettingsController::class, 'public']);
Route::get('/settings/profile-fields',  [GeneralSettingsController::class, 'profileFields']);
Route::get('/menus/{location}',         [MenuController::class, 'show']);
Route::get('/theme/css',                [ThemeController::class, 'css']);
Route::get('/theme/manifest',           [ThemeController::class, 'manifest']);
Route::get('/translations/{language}',  [LocalizationController::class, 'translations']);
Route::get('/pwa-config',               [GeneralSettingsController::class, 'pwaConfig']);

Route::middleware(['auth:sanctum', 'role:super_admin'])->group(function () {
    // General
    Route::get('/admin/settings',            [GeneralSettingsController::class, 'index']);
    Route::put('/admin/settings',            [GeneralSettingsController::class, 'update']);
    Route::put('/admin/settings/profile-fields', [GeneralSettingsController::class, 'updateProfileFields']);
    Route::put('/admin/settings/pwa',        [GeneralSettingsController::class, 'updatePwa']);

    // Appearance / Themes
    Route::get('/admin/appearance',          [AppearanceController::class, 'index']);
    Route::put('/admin/appearance',          [AppearanceController::class, 'update']);
    Route::get('/admin/themes',              [ThemeController::class, 'index']);
    Route::post('/admin/themes/activate',    [ThemeController::class, 'activate']);
    Route::post('/admin/themes/install',     [ThemeController::class, 'install']);
    Route::delete('/admin/themes/{slug}',    [ThemeController::class, 'remove']);

    // Menus
    Route::get('/admin/menus',               [MenuController::class, 'index']);
    Route::put('/admin/menus/{location}',    [MenuController::class, 'update']);

    // Email
    Route::get('/admin/settings/email',      [EmailSettingsController::class, 'show']);
    Route::put('/admin/settings/email',      [EmailSettingsController::class, 'update']);
    Route::post('/admin/settings/email/test',[EmailSettingsController::class, 'test']);

    // Storage
    Route::get('/admin/settings/storage',    [StorageSettingsController::class, 'show']);
    Route::put('/admin/settings/storage',    [StorageSettingsController::class, 'update']);

    // Auth settings
    Route::get('/admin/settings/auth',       [AuthSettingsController::class, 'show']);
    Route::put('/admin/settings/auth',       [AuthSettingsController::class, 'update']);

    // SEO
    Route::get('/admin/settings/seo',        [SeoSettingsController::class, 'show']);
    Route::put('/admin/settings/seo',        [SeoSettingsController::class, 'update']);
    Route::post('/admin/settings/robots-txt',[SeoSettingsController::class, 'updateRobotsTxt']);

    // Localizations
    Route::apiResource('admin/localizations', LocalizationController::class);

    // Plugin Manager
    Route::get('/admin/plugins',                   [PluginManagerController::class, 'index']);
    Route::post('/admin/plugins/{slug}/enable',    [PluginManagerController::class, 'enable']);
    Route::post('/admin/plugins/{slug}/disable',   [PluginManagerController::class, 'disable']);
    Route::delete('/admin/plugins/{slug}',         [PluginManagerController::class, 'remove']);
    Route::post('/admin/plugins/install',          [PluginManagerController::class, 'install']);

    // System
    Route::get('/admin/system/status',       [SystemController::class, 'status']);
    Route::post('/admin/system/clear-cache', [SystemController::class, 'clearCache']);
    Route::post('/admin/system/optimize',    [SystemController::class, 'optimize']);
    Route::post('/admin/system/migrate',     [SystemController::class, 'migrate']);
    Route::post('/admin/system/deploy',      [SystemController::class, 'deploy']);
    Route::get('/admin/system/git-log',      [SystemController::class, 'gitLog']);
});
