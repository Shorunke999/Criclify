<?php

use Illuminate\Support\Facades\Route;
use Modules\Auth\Http\Controllers\Admin\AccountController;
use Modules\Auth\Http\Controllers\AuthController;
use Modules\Auth\Http\Controllers\KycController;

Route::prefix('auth')->controller(AuthController::class)->group(function () {
    Route::post('signup', 'signup');
    Route::post('creator/invite','creatorInvite');
    Route::post('login', 'login');
    Route::post('forgot-password', 'forgotPassword');
    Route::get('email/verify/{id}/{hash}', 'verifyEmail');
    Route::post('reset-password', 'resetPassword');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', 'logout');
    });

});

/*
|--------------------------------------------------------------------------
| ADMIN ROUTES
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'role:admin'])
    ->prefix('admin')
    ->controller(AccountController::class)
    ->group(function () {

        Route::get('creators/pending', 'pending');

        Route::post('creators/{userId}/approve','approve');

        Route::post('creators/{userId}/deny','deny');
});
// ============================================
    //KYC ROUTES
// ============================================
Route::middleware(['auth:sanctum'])->prefix('kyc')->group(function () {
    Route::post('/verify', [KycController::class, 'submitVerification']);
    Route::get('/status', [KycController::class, 'getVerificationStatus']);
});
Route::post('/kyc/callback', [KycController::class, 'handleCallback']);

