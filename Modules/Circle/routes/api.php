<?php

use Illuminate\Support\Facades\Route;
use Modules\Circle\Http\Controllers\CircleController;
use Modules\Circle\Http\Controllers\ContributionController;

Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('circles')->controller(CircleController::class)->group(function () {

        // Create circle
        Route::post('/', 'store');

        // Join circle
        Route::post('{circleId}/join', 'join');

        // Invite users
        Route::post('{circleId}/invite', 'invite');

        // Accept invite (token-based)
        Route::post('invite/{token}/accept', 'acceptInvite');

        // Shuffle positions
        Route::post('{circleId}/shuffle', 'shufflePositions');

        //start cycle
        Route::post('{circleId}/start', 'startCycle');

        // Circle details
        Route::get('{circleId}', 'getCircleDetails');

        // List user circles (with filters)
        Route::get('/', 'listUserCircles');
    });

    Route::controller(ContributionController::class)->group(function () {
        Route::get('contributions', 'index');

        Route::get('my/contributions', 'myContributions');

        Route::get('circles/{circle}/contributions',
            'circleContributions'
        );

        Route::post('/members/{member}/contributions/pay','pay');
    });
});



