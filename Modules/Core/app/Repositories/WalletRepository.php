<?php
// Modules/Circle/Repositories/Eloquent/CircleRepository.php

namespace Modules\Core\Repositories;

use Modules\Core\Repositories\CoreRepository;
use Illuminate\Database\Eloquent\Model;
use Modules\Core\Models\Wallet;
use Modules\Core\Repositories\Contracts\WalletRepositoryInterface;
use Modules\Core\Enums\WalletTypeEnum;

class WalletRepository extends CoreRepository implements WalletRepositoryInterface
{
    protected Model $model;

    public function __construct(Wallet $wallet)
    {
        $this->model = $wallet;
    }

    public function creditWallet(int $id, float $amount, $type): void
    {
          $entityColumn = match ($type) {
                WalletTypeEnum::Circle => 'circle_id',
                WalletTypeEnum::User   => 'user_id',
            };

            $wallet = $this->model
                ->where($entityColumn, $id)
                ->where('type', $type)
                ->lockForUpdate()
                ->firstOrFail();

        $wallet->increment('balance', $amount);
    }

    public function debitWallet(int $id, float $amount, $type): void
    {
          $entityColumn = match ($type) {
                WalletTypeEnum::Circle => 'circle_id',
                WalletTypeEnum::User   => 'user_id',
            };

        $wallet = $this->model
            ->where($entityColumn, $id)
            ->where('type', $type)
            ->lockForUpdate()
            ->firstOrFail();

        $wallet->decrement('balance', $amount);
    }

}
