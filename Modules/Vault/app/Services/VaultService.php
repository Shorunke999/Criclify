<?php

namespace Modules\Vault\Services;

use App\Enums\AuditAction;
use App\Traits\ResponseTrait;
use Modules\Vault\Repositories\Contracts\VaultRepositoryInterface;
use Exception;
use Illuminate\Support\Facades\DB;
use Modules\Core\Events\AuditLogged;
use Modules\Vault\Models\Vault;
use  Modules\Core\Services\WalletService;
use Modules\Core\Enums\WalletTypeEnum;
class VaultService
{
    use ResponseTrait;

    public function __construct(
        protected VaultRepositoryInterface $vaultRepository,
        protected WalletService $walletService
    ) {}

    public function createVault(array $data, int $creatorId)
    {
        try {
            DB::beginTransaction();
            $vault = $this->vaultRepository->createVault($data,$creatorId);
            $this->walletService->debitWallet($creatorId,$vault->amount,WalletTypeEnum::User);
            // Dispatch event
            event(new AuditLogged(
                action: AuditAction::VAULT_CREATED->value,
                userId: $creatorId,
                entityType: get_class($vault),
                entityId: $vault->id
            ));

            DB::commit();

            return $this->success_response($vault, 'Goal created successfully');

        } catch (Exception $e) {
            DB::rollBack();
            $this->reportError($e, "VaultService", [
                'action' => 'create',
                'owner_id' => $creatorId,
                'data' => $data
            ]);
            return $this->error_response('Failed to create Goal: ' . $e->getMessage(),$e->getCode() ?: 400);
        }
    }

    public function getVaultDetails(Vault $vault, int $userId)
    {
        try {
            if (!$vault) {
                return $this->error_response('Goal not found or you are not the owner', 404);
            }
            $vaultDetails = $this->vaultRepository->getVaultPayments($vault->id);
            $data = [
                'vault' => $vault,
                'transaction_details' =>  $vaultDetails
            ];
            return $this->success_response($data, 'Goal details retrieved');

        } catch (Exception $e) {
            $this->reportError($e, "VaultService", [
                'action' => 'get_details',
                'Vault_id' => $vault->id,
                'user_id' => $userId
            ]);
            return $this->error_response('Failed to get Goal details');
        }
    }

    public function getUserVaults(array $filters = [],int $userId)
    {
        try {
            $circles = $this->vaultRepository->getUserVaults($userId, $filters);

            return $this->success_response($circles, 'User Goals retrieved');

        } catch (Exception $e) {
            $this->reportError($e, "Vaultervice", [
                'action' => 'list_user_vault',
                'user_id' => $userId,
                'filters' => $filters
            ]);
            return $this->error_response('Failed to retrieve Vault');
        }
    }

    //payment for vault
    //due vault update
}
