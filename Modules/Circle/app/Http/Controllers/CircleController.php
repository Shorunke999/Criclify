<?php

namespace Modules\Circle\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Circle\Http\Requests\CreateCircleRequest;
use Modules\Circle\Http\Requests\ListCircleRequest;
use Modules\Circle\Services\CircleService;

class CircleController extends Controller
{
    public function __construct(protected CircleService $circleService)
    {}
    public function store(CreateCircleRequest $request)
    {
        return $this->circleService->createCircle($request->validated(), auth()->id());
    }

    public function join(int $circleId)
    {
        return $this->circleService->joinCircle($circleId,auth()->id());
    }

    public function invite(Request $request, int $circleId)
    {
        return $this->circleService->inviteToCircle($circleId, $request->input('emails', []), auth()->id());
    }

    public function acceptInvite(string $token)
    {
        return $this->circleService->acceptInvite($token, auth()->id());
    }

    public function shufflePositions(int $circleId)
    {
        return $this->circleService->shufflePosition($circleId, auth()->id());
    }

    public function startCycle(int $circleId)
    {
        return $this->circleService->startCycle($circleId, auth()->id());
    }
    public function getCircleDetails(int $circleId)
    {
        return $this->circleService->getCircleDetails($circleId, auth()->id());
    }

    public function listUserCircles(ListCircleRequest $request)
    {
        return $this->circleService->getUserCircles($request->validated(),auth()->id());
    }
}
