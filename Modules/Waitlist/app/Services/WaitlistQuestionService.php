<?php

namespace Modules\Waitlist\Services;

use App\Traits\ResponseTrait;
use Exception;
use Modules\Waitlist\Repositories\Contracts\WaitlistQuestionRepositoryInterface;

class WaitlistQuestionService
{
    use ResponseTrait;

    public function __construct(
        protected WaitlistQuestionRepositoryInterface $repo
    ) {}

    public function list()
    {
        try{
            return $this->success_response(
                $this->repo->all(),
                'Survey questions fetched'
            );
        }catch(Exception $e){
            $this->reportError($e,"Waitlist",[
                 'action' => 'list Questions',
                 'service' => 'WaitlistQuestionService'
            ]);
            return $this->error_response('Error Listing Question: '.$e->getMessage(), $e->getCode() ?: 400);

        }

    }

    public function create(array $data)
    {
        try{
             $question = $this->repo->create($data);

            return $this->success_response(
                $question,
                'Survey question created',
                201
            );
        }catch(Exception $e)
        {
            $this->reportError($e,"Waitlist",[
                    'action' => 'create Questions',
                    'service' => 'WaitlistQuestionService'
            ]);
            return $this->error_response('Error Creating Question: '.$e->getMessage(), $e->getCode() ?: 400);

        }

    }

    public function update(int $id, array $data)
    {
        try{
               $this->repo->update($id, $data);

            return $this->success_response([], 'Survey question updated');
        }catch(Exception $e)
        {
            $this->reportError($e,"Waitlist",[
                    'action' => 'update Question',
                    'service' => 'WaitlistQuestionService'
            ]);
            return $this->error_response('Error Updating Question: '.$e->getMessage(), $e->getCode() ?: 400);

        }

    }

    public function toggle(int $id)
    {
        try{
            $question = $this->repo->find($id);

            $question->update([
                'active' => ! $question->active
            ]);

            return $this->success_response([], 'Survey question status updated');
        }catch(Exception $e)
        {
            $this->reportError($e,"Waitlist",[
                    'action' => 'toggle Question',
                    'service' => 'WaitlistQuestionService'
            ]);
            return $this->error_response('Error toggling Question: '.$e->getMessage(), $e->getCode() ?: 400);

        }
    }
}
