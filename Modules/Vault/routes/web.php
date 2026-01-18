<?php

use Illuminate\Support\Facades\Route;
use Modules\Vault\Http\Controllers\VaultController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('vaults', VaultController::class)->names('vault');
});
