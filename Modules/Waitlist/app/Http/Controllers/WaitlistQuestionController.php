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

    /**
     * List all waitlist questions
     */
    public function index()
    {
        return $this->service->list();
    }
    /**
     * Store a new waitlist question
     */
    public function store(StoreWaitlistQuestionRequest $request)
    {
        return $this->service->create($request->validated());
    }

    /**
     * Update an existing waitlist question
     */
    public function update(StoreWaitlistQuestionRequest $request, int $id)
    {
        return $this->service->update($id, $request->validated());
    }

    /**
     * Toggle the active status of a waitlist question
     */
    public function toggle(int $id)
    {
        return $this->service->toggle($id);
    }
}
