<?php

namespace Modules\Auth\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Auth\Services\AccountService;

class AccountController extends Controller
{
    public function __construct(
        protected AccountService $accountService
    ) {}

    /**
     * Get Pending Account Invitations
     *
     */
    public function pending()
    {
        return $this->accountService->getPendingCreatorAccounts();
    }

    /**
     * Approve Account Invitation
     *
     */
    public function approve(int $userId)
    {
        return $this->accountService->approveCreatorAccount($userId);
    }

    /**
     * Deny Account Invitation
     *
     */
    public function deny(Request $request, int $userId)
    {
        return $this->accountService->denyCreatorAccount(
            $userId,
            $request->input('reason')
        );
    }
}
