<?php

use Illuminate\Support\Facades\Route;
use Modules\Notification\Http\Controllers\InappNotificationController;
use Modules\Notification\Http\Controllers\NotificationController;


Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    Route::controller(InappNotificationController::class)->group(function () {
        Route::get('inapp-notifications', 'index');
        Route::post('inapp-notifications/mark-as-read/{id}', 'markAsRead');
        Route::post('inapp-notifications/mark-all-as-read', 'markAllAsRead');
    });

});
