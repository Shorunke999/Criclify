<?php
// Modules/Circle/Repositories/Contracts/CircleRepositoryInterface.php

namespace Modules\Vault\Repositories\Contracts;

use Modules\Core\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Support\LazyCollection;
use Modules\Vault\Models\Vault;

interface VaultRepositoryInterface extends BaseRepositoryInterface
{
    public function overdueVaultPayment(): ?LazyCollection;
    public function getUserVaults(int $userId, array $filters = []);
    public function createVault(array $data, int $ownerId): Vault;
    public function getVaultPayments(int $vaultId);
}
