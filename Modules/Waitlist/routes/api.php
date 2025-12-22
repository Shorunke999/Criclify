<?php

use Illuminate\Support\Facades\Route;
use Modules\Waitlist\Http\Controllers\WaitlistController;
use Modules\Waitlist\Http\Controllers\WaitlistQuestionController;

Route::middleware(['auth:sanctum', 'can:admin'])
    ->prefix('admin/waitlist')
    ->group(function () {
        Route::get('/questions', [WaitlistQuestionController::class, 'index']);
        Route::post('/questions', [WaitlistQuestionController::class, 'store']);
        Route::put('/questions/{id}', [WaitlistQuestionController::class, 'update']);
        Route::patch('/questions/{id}/toggle', [WaitlistQuestionController::class, 'toggle']);
        Route::get('/export', [WaitlistController::class, 'export']);
    });

Route::post('/waitlist', [WaitlistController::class, 'store']);

