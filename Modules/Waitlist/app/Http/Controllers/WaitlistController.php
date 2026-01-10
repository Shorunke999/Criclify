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

    /**
     * Join the waitlist
     */
    public function store(JoinWaitlistRequest $request)
    {
        return $this->service->join($request->validated());
    }

    /**
     * Export waitlist data
     */
    public function export(WaitlistExportRequest $request)
    {
        return $this->service->export($request->validated());
    }
}
