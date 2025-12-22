<?php

use Illuminate\Support\Facades\Route;
use Modules\Auth\Http\Controllers\AuthController;
use Modules\Auth\Http\Controllers\KycController;

Route::prefix('auth')->controller(AuthController::class)->group(function () {
    Route::post('signup', 'signup');
    Route::post('login', 'login');
    Route::post('forgot-password', 'forgotPassword');
    Route::get('email/verify/{id}/{hash}', 'verifyEmail');
    Route::post('reset-password', 'resetPassword');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', 'logout');
    });

});

// ============================================
    //KYC ROUTES
// ============================================
Route::middleware(['auth:sanctum'])->prefix('kyc')->group(function () {
    Route::post('/verify', [KycController::class, 'submitVerification']);
    Route::get('/status', [KycController::class, 'getVerificationStatus']);
});
Route::post('/kyc/callback', [KycController::class, 'handleCallback']);
