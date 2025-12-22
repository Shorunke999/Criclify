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
     * GET /api/referral/code
     */
    public function generate()
    {
        return $this->service->generate();
    }

    /**
     * GET /api/referral/leaderboard
     * (Admin or public – your choice)
     */
    public function leaderboard()
    {
        return $this->service->leaderboard();
    }
}
