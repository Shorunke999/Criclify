<?php

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Auth\Http\Requests\SubmitVerification;
use Modules\Auth\Services\KycService;

class KycController extends Controller
{

    public function __construct(
        protected KycService $kycService
    ) {}

    /**
     * Submit KYC verification
     */
    public function submitVerification(SubmitVerification $request)
    {
        return $this->kycService->submitVerification($request->validated());
    }
    /**
     * Handle KYC provider callback
     */
    public function handleCallback(Request $request)
    {
        return $this->kycService->handleCallback($request);
    }

    /**
     * Get KYC verification status
     */
    public function getVerificationStatus()
    {
       return $this->kycService->getVerificationStatus();
    }
}
