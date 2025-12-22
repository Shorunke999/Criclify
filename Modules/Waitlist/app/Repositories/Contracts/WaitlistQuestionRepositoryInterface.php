<?php
namespace Modules\Waitlist\Repositories\Contracts;

use Modules\Core\Repositories\Contracts\BaseRepositoryInterface;

interface WaitlistQuestionRepositoryInterface extends BaseRepositoryInterface {
      public function active();
}
