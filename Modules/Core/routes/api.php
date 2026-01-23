<?php

use Illuminate\Support\Facades\Route;
use Modules\Core\Http\Controllers\Admin\CountryController;

Route::middleware(['auth:sanctum', 'role:admin'])
    ->prefix('admin')
    ->group(function () {
        Route::get('countries', [CountryController::class, 'index']);
        Route::post('countries', [CountryController::class, 'store']);
        Route::put('countries/{id}', [CountryController::class, 'update']);
        Route::get(
            'reference/countries',
            [CountryController::class, 'listReference']
        );
    });
