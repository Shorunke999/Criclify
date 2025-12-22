<?php

namespace Modules\Auth\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Enums\KycStatus;
use App\Traits\ResponseTrait;

class EnsureKycVerified
{
    use ResponseTrait;
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        if ($request->user->kyc_status !== KycStatus::VERIFIED) {
            return $this->error_response('KYC verification required. Next step is '.$request->user()->kyc_status->nextStep()
            ,403);
        }
        return $next($request);
    }
}
