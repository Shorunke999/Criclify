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
    Route::post('verify-email', 'verifyEmail');
    Route::post('reset-password', 'resetPassword');
    Route::post('/resend-otp', 'resendOtp');

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

