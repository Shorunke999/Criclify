<?php

use Illuminate\Support\Facades\Route;
use Modules\Payment\Http\Controllers\PaymentController;

Route::post('/payment/webhooks', [PaymentController::class, 'paystack_webhook']);

