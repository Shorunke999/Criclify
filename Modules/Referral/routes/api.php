<?php

use Illuminate\Support\Facades\Route;
use Modules\Referral\Http\Controllers\ReferralController;

Route::middleware('auth:sanctum')->prefix('referral')->group(function () {
    Route::get('/code', [ReferralController::class, 'generate']);
});

Route::middleware(['auth:sanctum', 'can:admin'])
    ->get('/referral/leaderboard', [ReferralController::class, 'leaderboard']);
