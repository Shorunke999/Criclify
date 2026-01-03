<?php
// Modules/Circle/Repositories/Contracts/CircleRepositoryInterface.php

namespace Modules\Circle\Repositories\Contracts;

use Modules\Core\Repositories\Contracts\BaseRepositoryInterface;

interface CircleWalletRepositoryInterface extends BaseRepositoryInterface
{
    public function creditCircleWallet(int $circleId, float $amount): void;
    public function debitCircleWallet(int $circleId, float $amount): void;
}
