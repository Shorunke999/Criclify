<?php
// Modules/Circle/Repositories/Contracts/CircleRepositoryInterface.php

namespace Modules\Core\Repositories\Contracts;

use Modules\Core\Repositories\Contracts\BaseRepositoryInterface;
use Modules\Core\Enums\WalletTypeEnum;
use Modules\Core\Models\Wallet;

interface WalletRepositoryInterface extends BaseRepositoryInterface
{
    public function wallet(int $id, float $amount,WalletTypeEnum $type): Wallet;
}
