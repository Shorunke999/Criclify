<?php
// Modules/Circle/Repositories/Eloquent/CircleRepository.php

namespace Modules\Circle\Repositories;

use Modules\Core\Repositories\CoreRepository;
use Illuminate\Database\Eloquent\Model;
use Modules\Circle\Models\CircleWallet;
use Modules\Circle\Repositories\Contracts\CircleWalletRepositoryInterface;

class CircleWalletRepository extends CoreRepository implements CircleWalletRepositoryInterface
{
    protected Model $model;

    public function __construct(CircleWallet $wallet)
    {
        $this->model = $wallet;
    }

    public function creditCircleWallet(int $circleId, float $amount): void
    {
        $wallet =$this->model->where('circle_id', $circleId)
            ->lockForUpdate()
            ->firstOrFail();

        $wallet->increment('balance', $amount);
    }
     public function debitCircleWallet(int $circleId, float $amount): void
    {
        $wallet =$this->model->where('circle_id', $circleId)
            ->lockForUpdate()
            ->firstOrFail();

        $wallet->decrement('balance', $amount);
    }

}
