<?php
namespace Modules\Waitlist\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Waitlist\Http\Requests\StoreWaitlistQuestionRequest;
use Modules\Waitlist\Services\WaitlistQuestionService;

class WaitlistQuestionController extends Controller
{
    public function __construct(
        protected WaitlistQuestionService $service
    ) {}

    public function index()
    {
        return $this->service->list();
    }

    public function store(StoreWaitlistQuestionRequest $request)
    {
        return $this->service->create($request->validated());
    }

    public function update(StoreWaitlistQuestionRequest $request, int $id)
    {
        return $this->service->update($id, $request->validated());
    }

    public function toggle(int $id)
    {
        return $this->service->toggle($id);
    }
}
