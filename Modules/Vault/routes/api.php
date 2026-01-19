<?php

use Illuminate\Support\Facades\Route;
use Modules\Vault\Http\Controllers\VaultController;


Route::middleware('auth:sanctum')->group(function () {

    Route::prefix('vaults')->group(function () {
        Route::post('/', [VaultController::class, 'store']);
        Route::get('/', [VaultController::class, 'index']);
        Route::get('{vault}', [VaultController::class, 'show']);
        Route::post('{vault}/pay', [VaultController::class, 'pay']);
        Route::post('{vault}/disburse',[VaultController::class,'disburse']);
    });

});
