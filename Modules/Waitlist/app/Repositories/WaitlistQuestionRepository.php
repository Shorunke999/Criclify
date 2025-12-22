<?php
namespace Modules\Waitlist\Repositories;

use Modules\Core\Repositories\CoreRepository;
use Modules\Waitlist\Models\WaitlistQuestion;
use Modules\Waitlist\Repositories\Contracts\WaitlistQuestionRepositoryInterface;

class WaitlistQuestionRepository extends CoreRepository implements WaitlistQuestionRepositoryInterface
{
    public function __construct(WaitlistQuestion $model)
    {
        $this->model = $model;
    }
    public function active()
    {
        return $this->model
            ->where('active', true)
            ->orderBy('id')
            ->get();
    }
}
