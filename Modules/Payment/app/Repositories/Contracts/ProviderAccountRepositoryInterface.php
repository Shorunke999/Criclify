<?php


namespace Modules\Payment\Repositories\Contracts;

use Modules\Core\Repositories\Contracts\BaseRepositoryInterface;
use Modules\Payment\Models\ProviderAccount;

interface ProviderAccountRepositoryInterface extends BaseRepositoryInterface
{
    // ProviderAccountRepositoryInterface
    public function findByUserAndProvider(int $userId, string $provider): ?ProviderAccount;

}
