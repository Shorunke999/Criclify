<?php

namespace Modules\Waitlist\Services;

use App\Traits\ResponseTrait;
use Modules\Waitlist\Repositories\Contracts\WaitlistQuestionRepositoryInterface;

class WaitlistQuestionService
{
    use ResponseTrait;

    public function __construct(
        protected WaitlistQuestionRepositoryInterface $repo
    ) {}

    public function list()
    {
        return $this->success_response(
            $this->repo->all(),
            'Survey questions fetched'
        );
    }

    public function create(array $data)
    {
        $question = $this->repo->create($data);

        return $this->success_response(
            $question,
            'Survey question created',
            201
        );
    }

    public function update(int $id, array $data)
    {
        $this->repo->update($id, $data);

        return $this->success_response([], 'Survey question updated');
    }

    public function toggle(int $id)
    {
        $question = $this->repo->find($id);

        $question->update([
            'active' => ! $question->active
        ]);

        return $this->success_response([], 'Survey question status updated');
    }
}
