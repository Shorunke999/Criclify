<?php

namespace Modules\Payment\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Payment\Services\PaymentService;

class PaymentController extends Controller
{
    public function __construct(protected PaymentService $paymentService)
    {}

    public function paystack_webhook(Request $request )
    {
        return $this->paymentService->handleWebhook($request);
    }
}
