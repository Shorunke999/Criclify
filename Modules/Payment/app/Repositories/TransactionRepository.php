<?php
// Modules/Circle/Repositories/Eloquent/CircleRepository.php

namespace Modules\Payment\Repositories;

use Modules\Core\Repositories\CoreRepository;
use Illuminate\Database\Eloquent\Model;
use Modules\Payment\Models\Transaction;
use Modules\Payment\Repositories\Contracts\TransactionRepositoryInterface;

class TransactionRepository extends CoreRepository implements TransactionRepositoryInterface
{
    protected Model $model;

    public function __construct(Transaction $transaction)
    {
        $this->model = $transaction;
    }
}
