<?php

namespace Modules\Payment\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Payment\Services\TransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function __construct(
        protected TransactionService $transactionService
    ) {}

    /**
     * Handle payment provider webhooks
     *
     * @return JsonResponse
     */
    public function handle(): JsonResponse
    {
        return $this->transactionService->processWebhook();
    }
}
