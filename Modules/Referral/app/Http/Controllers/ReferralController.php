<?php

namespace Modules\Referral\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Referral\Services\ReferralService;

class ReferralController extends Controller
{
    public function __construct(
        protected ReferralService $service
    ) {}

    /**
     * Generate a referral code for the authenticated useer or waitliat
     */
    public function generate()
    {
        return $this->service->generate();
    }

    /**
     * Get the referral leaderboard
     */
    public function leaderboard()
    {
        return $this->service->leaderboard();
    }
}
