<?php
// Modules/Circle/Repositories/Eloquent/CircleRepository.php

namespace Modules\Vault\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\Vault\Repositories\Contracts\VaultRepositoryInterface;
use Modules\Core\Repositories\CoreRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\LazyCollection;
use Modules\Vault\Models\Vault;
use Modules\Payment\Models\Transaction;
use Modules\Payment\Enums\TransactionTypeEnum;
use  Modules\Vault\Enums\VaultStatusEnum;

class VaultRepository extends CoreRepository implements VaultRepositoryInterface
{
    protected Model $model;

    public function __construct(Vault $vault)
    {
        $this->model = $vault;
    }

    public function getUserVaults(int $userId, array $filters = []): \Illuminate\Pagination\LengthAwarePaginator
    {
        $query = $this->model
            ->where('maturity_date','<',now());

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['search'])) {
            $query->where('name', 'like', '%' . $filters['search'] . '%');
        }

        return $query->paginate($filters['per_page'] ?? 15);
    }

    public function overdueVaultPayment(): ?LazyCollection
    {
       return Vault::whereDate('maturity_date', '<', now())->get();
    }

    public function getVaultPayments(int $vaultId){
        return Transaction::where('type',TransactionTypeEnum::Vault)
                ->where('type_ids', [$vaultId])->get();
     }

     public function maturedAndCompletedVault():Collection
     {
        return $this->model->where('status',VaultStatusEnum::COMPLETED)
                ->where('maturity_date','<',now())
                ->get();
     }

}
