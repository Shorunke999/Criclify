<?php
// Modules/Circle/Repositories/Eloquent/CircleRepository.php

namespace Modules\Payment\Repositories;

use Modules\Core\Repositories\CoreRepository;
use Illuminate\Database\Eloquent\Model;
use Modules\Payment\Models\WithdrawalAccount;
use Modules\Payment\Repositories\Contracts\WithdrawalAccountRepositoryInterface;

class WithdrawalAccountRepository extends CoreRepository implements WithdrawalAccountRepositoryInterface
{
    protected Model $model;

    public function __construct(WithdrawalAccount $provider)
    {
        $this->model = $provider;
    }
}
