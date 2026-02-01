<?php

use Illuminate\Support\Facades\Route;

use Modules\Cooperative\Http\Controllers\CooperativeAuthController;

Route::prefix('cooperative')
    ->middleware(['auth:sanctum','role:cooperative'])
    ->group(function () {
        Route::post(
            '/create/api-keys',
            [CooperativeAuthController::class, 'generateApiKey']
        );
        Route::get(
            '/api-keys',
            [CooperativeAuthController::class, 'getKeys']
        );
        Route::delete(
            '/api-keys/{keyId}',
            [CooperativeAuthController::class, 'deleteKey']
        );
    });

Route::prefix('cooperative')
    ->middleware(['auth:sanctum','role:admin'])
    ->group(function () {
        Route::post(
            '/create/api-permission',
            [CooperativeAuthController::class, 'createApiPermission']
        );
    });
