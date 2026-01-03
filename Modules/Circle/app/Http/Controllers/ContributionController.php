<?php

namespace Modules\Circle\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Circle\Http\Requests\Contribution\ListContributionRequest;
use Modules\Circle\Http\Requests\Contribution\PayContributionRequest;

class ContributionController extends Controller
{
    protected $contributionService;

    public function __construct(\Modules\Circle\Services\ContributionService $contributionService)
    {
        $this->contributionService = $contributionService;
    }

     /**
     * List my contributions
     */
   public function myContributions(ListContributionRequest $request)
    {
        return $this->contributionService->listContributions(
            array_merge(
                $request->validated(),
                ['user_id' => auth()->id()]
            )
        );
    }

    public function circleContributions(
    int $circleId,
    ListContributionRequest $request
    ) {
        return $this->contributionService->listContributions(
            array_merge(
                $request->validated(),
                ['circle_id' => $circleId]
            )
        );
    }

    public function index(ListContributionRequest $request)
    {
        return $this->contributionService->listContributions(
            $request->validated()
        );
    }

    public function pay(PayContributionRequest $request, int $member)
    {
        return $this->contributionService->payForContribution(
            memberId: $member,
            data: $request->validated(),
            contributionId: $request->input('contribution_id')
        );
    }


}
