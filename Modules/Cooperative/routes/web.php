<?php

use Illuminate\Support\Facades\Route;
use Modules\Cooperative\Http\Controllers\CooperativeController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('cooperatives', CooperativeController::class)->names('cooperative');
});
