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
}
