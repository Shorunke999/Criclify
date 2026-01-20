<?php
// Modules/Circle/Repositories/Contracts/CircleRepositoryInterface.php

namespace Modules\Vault\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Support\LazyCollection;
use Modules\Vault\Models\Vault;

interface VaultRepositoryInterface extends BaseRepositoryInterface
{
    public function overdueVaultPayment(): ?LazyCollection;
    public function getUserVaults(int $userId, array $filters = []);
    public function getVaultPayments(int $vaultId);
    public function maturedAndCompletedVault():Collection;
}
