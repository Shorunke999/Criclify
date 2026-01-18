<?php
// Modules/Circle/Repositories/Contracts/CircleRepositoryInterface.php

namespace Modules\Core\Repositories\Contracts;

use Modules\Core\Repositories\Contracts\BaseRepositoryInterface;
use Modules\Core\Enums\WalletTypeEnum;

interface WalletRepositoryInterface extends BaseRepositoryInterface
{
    public function creditWallet(int $id, float $amount,WalletTypeEnum $type): void;
    public function debitWallet(int $id, float $amount,WalletTypeEnum $type): void;
}
