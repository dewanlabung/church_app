<?php

use Illuminate\Support\Facades\Route;
use Plugins\Notification\Controllers\NotificationController;
use Plugins\Notification\Controllers\NotificationPreferenceController;
use Plugins\Notification\Controllers\AdminNotificationController;

Route::middleware('auth:sanctum')->group(function () {
    // User notifications
    Route::get('/notifications',              [NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::patch('/notifications/{id}/read',  [NotificationController::class, 'markRead']);
    Route::post('/notifications/read-all',    [NotificationController::class, 'markAllRead']);
    Route::delete('/notifications/{id}',      [NotificationController::class, 'destroy']);

    // Push subscription (PWA VAPID)
    Route::post('/notifications/push/subscribe',   [NotificationController::class, 'subscribe']);
    Route::delete('/notifications/push/unsubscribe',[NotificationController::class, 'unsubscribe']);

    // User preferences
    Route::get('/notifications/preferences',   [NotificationPreferenceController::class, 'show']);
    Route::put('/notifications/preferences',   [NotificationPreferenceController::class, 'update']);

    // Admin: broadcast announcement
    Route::middleware('role:super_admin')->group(function () {
        Route::get('/admin/notifications',                    [AdminNotificationController::class, 'index']);
        Route::post('/admin/notifications/broadcast',         [AdminNotificationController::class, 'broadcast']);
        Route::get('/admin/notifications/settings',           [AdminNotificationController::class, 'settings']);
        Route::put('/admin/notifications/settings',           [AdminNotificationController::class, 'updateSettings']);
    });
});
