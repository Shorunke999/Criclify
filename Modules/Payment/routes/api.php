<?php

use Illuminate\Support\Facades\Route;
use Modules\Payment\Http\Controllers\CustomerController;
use Modules\Payment\Http\Controllers\TransactionController;
use Modules\Payment\Http\Controllers\WebhookController;

Route::prefix('payment')->middleware(['auth:sanctum'])->controller(CustomerController::class)->group(function () {

    // Customer Onboarding
    Route::prefix('customer')->group(function () {
        Route::post('/onboard', 'onboard')
            ->name('payment.customer.onboard');

        Route::get('/details', 'show')
            ->name('payment.customer.show');
    });

    // Withdrawal Accounts
    Route::prefix('withdrawal-accounts')->group(function () {
        Route::get('/', 'getWithdrawalAccounts')
            ->name('payment.withdrawal-accounts.index');

        Route::post('/', 'addWithdrawalAccount')
            ->name('payment.withdrawal-accounts.store');

        Route::delete('/{recipientCode}', 'removeWithdrawalAccount')
            ->name('payment.withdrawal-accounts.destroy');

        Route::patch('/default', 'setDefaultWithdrawalAccount')
            ->name('payment.withdrawal-accounts.set-default');
    });
});

Route::prefix('payment')->group(function () {

    // Protected transaction routes
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::controller(TransactionController::class)->group(function () {
            // Withdrawals
            Route::post('/withdraw', 'withdraw')
                ->name('payment.withdraw');

            // Transaction history
            Route::get('/transactions', 'index')
                ->name('payment.transactions.index');

            Route::get('/transactions/{transactionId}', 'show')
                ->name('payment.transactions.show');

              // Banks
            Route::get('/banks', 'banks')
                ->name('payment.banks.list');

            Route::post('/banks/verify', 'verifyBankAccount')
                ->name('payment.banks.verify');

        });

    });
    // Webhook endpoint (no auth middleware - verified by signature)
    Route::post('/webhook', [WebhookController::class, 'handle'])
        ->name('payment.webhook');
});
