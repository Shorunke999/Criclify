<?php

namespace Modules\Cooperative\Repositories;

use Illuminate\Database\Eloquent\Model;
use Modules\Cooperative\Models\CooperativeApiPermission;
use Modules\Cooperative\Repositories\Contracts\CooperativeApiPermissionRepositoryInterface;
use Modules\Core\Repositories\CoreRepository;
class CooperativeApiPermissionRepository extends CoreRepository implements CooperativeApiPermissionRepositoryInterface
{
    protected Model $model;

    public function __construct(CooperativeApiPermission $cooperativeApiPermission)
    {
        $this->model = $cooperativeApiPermission;
    }
}
