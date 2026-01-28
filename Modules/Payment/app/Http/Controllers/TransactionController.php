<?php

namespace Modules\Payment\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ResponseTrait;
use Modules\Payment\Services\TransactionService;
use Modules\Payment\Http\Requests\InitiateWithdrawalRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Payment\Http\Requests\VerifyBankAccountRequest;

class TransactionController extends Controller
{
    use ResponseTrait;
    public function __construct(
        protected TransactionService $transactionService,

    ) {}

     /**
     * Get list of banks
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function banks(Request $request): JsonResponse
    {
        $country = $request->query('country', 'NG');
        return $this->transactionService->getBanks($country);
    }

    /**
     * Verify bank account number
     *
     * @param VerifyBankAccountRequest $request
     * @return JsonResponse
     */
    public function verifyBankAccount(VerifyBankAccountRequest $request): JsonResponse
    {
        $validated = $request->validated();

        return $this->transactionService->verifyBankAccount(
                $validated['account_number'],
                $validated['bank_code']
        );
    }
    /**
     * Initiate withdrawal from wallet to bank account
     *
     * @param InitiateWithdrawalRequest $request
     * @return JsonResponse
     */
    public function withdraw(InitiateWithdrawalRequest $request): JsonResponse
    {
        $userId = auth()->id();

        return $this->transactionService->initiateWithdrawal($userId, $request->validated());
    }

    /**
     * Get user's transaction history
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $userId = auth()->id();

        $filters = $request->only(['type', 'status', 'from_date', 'to_date', 'circle_id']);

        return response()->json(
            $this->transactionService->getUserTransactions($userId, $filters)
        );
    }

    /**
     * Get single transaction details
     *
     * @param int $transactionId
     * @return JsonResponse
     */
    public function show(int $transactionId): JsonResponse
    {
        $transaction = $this->transactionService->getTransaction($transactionId);

        if (!$transaction) {
            return $this->error_response('Transaction not found',404);
        }

        // Authorization check - ensure user owns this transaction
        if ($transaction->user_id !== auth()->id()) {
            return $this->error_response('Unauthorized access',403);
        }

        return $this->success_response($transaction,'Transaction fetched successfully', 200);
    }
}
