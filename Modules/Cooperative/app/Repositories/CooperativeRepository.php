<?php

namespace Modules\Cooperative\Repositories;

use Illuminate\Database\Eloquent\Model;
use Modules\Cooperative\Models\Cooperative;
use Modules\Cooperative\Repositories\Contracts\CooperativeRepositoryInterface;
use Modules\Core\Repositories\CoreRepository;
class CooperativeRepository extends CoreRepository implements CooperativeRepositoryInterface
{
    protected Model $model;

    public function __construct(Cooperative $cooperative)
    {
        $this->model = $cooperative;
    }
}
