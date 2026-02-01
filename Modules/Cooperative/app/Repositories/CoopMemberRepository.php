<?php

namespace Modules\Cooperative\Repositories;

use Illuminate\Database\Eloquent\Model;
use Modules\Cooperative\Models\CoopMember;
use Modules\Cooperative\Repositories\Contracts\CoopMemberRepositoryInterface;
use Modules\Core\Repositories\CoreRepository;
class CoopMemberRepository extends CoreRepository implements CoopMemberRepositoryInterface
{
    protected Model $model;

    public function __construct(CoopMember $cooperativeMember)
    {
        $this->model = $cooperativeMember;
    }
}
