<?php
namespace Modules\Waitlist\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Waitlist\Http\Requests\JoinWaitlistRequest;
use Modules\Waitlist\Http\Requests\WaitlistExportRequest;
use Modules\Waitlist\Services\WaitlistService;

class WaitlistController extends Controller
{
    public function __construct(
        protected WaitlistService $service
    ) {}

    public function store(JoinWaitlistRequest $request)
    {
        return $this->service->join($request->validated());
    }

    public function export(WaitlistExportRequest $request)
    {
        return $this->service->export($request->validated());
    }
}
