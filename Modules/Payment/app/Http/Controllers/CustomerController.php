<?php

namespace Modules\Payment\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Payment\Services\CustomerService;
use Modules\Payment\Http\Requests\OnboardCustomerRequest;
use Modules\Payment\Http\Requests\AddWithdrawalAccountRequest;
use Modules\Payment\Http\Requests\SetDefaultWithdrawalAccountRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function __construct(
        protected CustomerService $customerService
    ) {}

    /**
     * Onboard customer with bank provider
     *
     * @param OnboardCustomerRequest $request
     * @return JsonResponse
     */
    public function onboard(OnboardCustomerRequest $request): JsonResponse
    {
        return $this->customerService->onboardCustomer($request->validated());
    }

    /**
     * Get customer details
     *
     * @return JsonResponse
     */
    public function show(): JsonResponse
    {
        $userId = auth()->id();
        return $this->customerService->getCustomerDetails($userId);
    }


    /**
     * Add withdrawal account
     *
     * @param AddWithdrawalAccountRequest $request
     * @return JsonResponse
     */
    public function addWithdrawalAccount(AddWithdrawalAccountRequest $request): JsonResponse
    {
        $userId = auth()->id();

        return $this->customerService->addWithdrawalAccount($userId, $request->validated());
    }

    /**
     * Get user's withdrawal accounts
     *
     * @return JsonResponse
     */
    public function getWithdrawalAccounts(): JsonResponse
    {
        $userId = auth()->id();

        return $this->customerService->getWithdrawalAccounts($userId);
    }

    /**
     * Remove withdrawal account
     *
     * @param string $recipientCode
     * @return JsonResponse
     */
    public function removeWithdrawalAccount(string $recipientCode): JsonResponse
    {
        $userId = auth()->id();

        return $this->customerService->removeWithdrawalAccount($userId, $recipientCode);
    }

    /**
     * Set default withdrawal account
     *
     * @param SetDefaultWithdrawalAccountRequest $request
     * @return JsonResponse
     */
    public function setDefaultWithdrawalAccount(SetDefaultWithdrawalAccountRequest $request): JsonResponse
    {
        $userId = auth()->id();

        return $this->customerService->setDefaultWithdrawalAccount(
                $userId,
                $request->validated()['recipient_code']
            );
    }
}
