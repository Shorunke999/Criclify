<?php
// Modules/Circle/Repositories/Eloquent/CircleRepository.php

namespace Modules\Payment\Repositories;

use Modules\Core\Repositories\CoreRepository;
use Illuminate\Database\Eloquent\Model;
use Modules\Payment\Models\ProviderAccount;
use Modules\Payment\Repositories\Contracts\ProviderAccountRepositoryInterface;

class ProviderAccountRepository extends CoreRepository implements ProviderAccountRepositoryInterface
{
    protected Model $model;

    public function __construct(ProviderAccount $provider)
    {
        $this->model = $provider;
    }

    public function findByUserAndProvider(int $userId, string $provider): ?ProviderAccount
    {
        return ProviderAccount::where('user_id', $userId)
            ->where('provider', $provider)
            ->first();
    }
}
