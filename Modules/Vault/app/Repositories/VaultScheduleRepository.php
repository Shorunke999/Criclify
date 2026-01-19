<?php
// Modules/Circle/Repositories/Eloquent/CircleRepository.php

namespace Modules\Vault\Repositories;

use Modules\Vault\Repositories\Contracts\VaultScheduleRepositoryInterface;
use Modules\Core\Repositories\CoreRepository;
use Illuminate\Database\Eloquent\Model;
use Modules\Vault\Models\VaultSchedule;


class VaultScheduleRepository extends CoreRepository implements VaultScheduleRepositoryInterface
{
    protected Model $model;

    public function __construct(VaultSchedule $schedule)
    {
        $this->model = $schedule;
    }

}
