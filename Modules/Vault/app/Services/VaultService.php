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
use Modules\Vault\Repositories\Contracts\VaultScheduleRepositoryInterface;
use Modules\Vault\Enums\VaultScheduleStatusEnum;
use  Modules\Vault\Enums\VaultStatusEnum;
class VaultService
{
    use ResponseTrait;

    public function __construct(
        protected VaultRepositoryInterface $vaultRepository,
        protected WalletService $walletService,
        protected VaultScheduleRepositoryInterface $scheduleRepo
    ) {}

    public function createVault(array $data, int $creatorId)
    {
        try {
            DB::beginTransaction();
            $vault = $this->vaultRepository->create($this->resolveCreateData($data,$creatorId));
            $this->walletService->debitWallet($creatorId,$vault->interval_amount,WalletTypeEnum::User);
            $this->generateVaultSchedule($vault);
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
            if ($vault->owner_id !== $userId) {
                return $this->error_response('Unauthorized', 403);
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
            $vaults = $this->vaultRepository->getUserVaults($userId, $filters);

            return $this->success_response($vaults, 'User Goals retrieved');

        } catch (Exception $e) {
            $this->reportError($e, "Vaultervice", [
                'action' => 'list_user_vault',
                'user_id' => $userId,
                'filters' => $filters
            ]);
            return $this->error_response('Failed to retrieve Vault');
        }
    }

    public function payForVault(Vault $vault, int $userId)
    {
        try {
            DB::beginTransaction();

            if ($vault->owner_id !== $userId) {
                return $this->error_response('Unauthorized', 403);
            }

            $schedule = $vault->schedules()
                ->where('status', VaultScheduleStatusEnum::PENDING)
                ->orderBy('due_date')
                ->first();

            if (! $schedule) {
                return $this->error_response('Vault fully paid', 400);
            }

            // debit wallet
            $this->walletService->debitWallet(
                $userId,
                $schedule->amount_due,
                WalletTypeEnum::User
            );

            // mark schedule as paid
            $schedule->update([
                'status' => VaultScheduleStatusEnum::PAID,
                'paid_at' => now(),
            ]);

            // update vault owing flag
            $this->updateVaultOwingStatus($vault);

            // check completion
            if ($vault->schedules()->where('status', VaultScheduleStatusEnum::PENDING)->count() === 0) {
                $vault->update(['status' => VaultStatusEnum::COMPLETED]);
            }
            DB::commit();
            return $this->success_response($vault->fresh(), 'Goal payment successful');

        } catch (Exception $e) {
            DB::rollBack();
            $this->reportError($e, 'VaultService', [
                'action' => 'pay_for_vault',
                'vault_id' => $vault->id,
            ]);
            return $this->error_response('Goal payment failed', 400);
        }
    }

    public function unlockCompletedVault():void
    {
        try {
            $maturedVaults = $this->vaultRepository->maturedAndCompletedVault();
            foreach($maturedVaults as $maturedVault)
            {
                $maturedVault->status = VaultStatusEnum::UNLOCKED;
                $maturedVault->save();
            }

        } catch (Exception $e) {
            $this->reportError($e, 'VaultService', [
                'action' => 'unlock_completed_vault',
            ]);
        }
    }

    public function disbursedTowallet(Vault $vault, int $userId)
    {
         try {
            DB::beginTransaction();

            if ($vault->owner_id !== $userId) {
                return $this->error_response('Unauthorized', 403);
            }
             if($vault->status != VaultStatusEnum::UNLOCKED->value) return $this->error_response('This Goal can not be disbursed and its not unlocked', 422);
            // // debit wallet
            $this->walletService->creditWallet(
                $userId,
                $vault->total_amount,
                WalletTypeEnum::User
            );
            // mark schedule as paid
            $vault->update([
                'status' => VaultStatusEnum::DISBURSED,
            ]);
            event(new AuditLogged(
                action: AuditAction::VAULT_DISBUSED->value,
                userId: $userId,
                entityType: get_class($vault),
                entityId: $vault->id
            ));
            DB::commit();
            return $this->success_response($vault->fresh(), 'Goal disbursement successful');

        } catch (Exception $e) {
            DB::rollBack();
            $this->reportError($e, 'VaultService', [
                'action' => 'disbursed_vault_to_wallet',
                'vault_id' => $vault->id,
            ]);
            return $this->error_response('Goal disbursement failed', 400);
        }
    }
    private function resolveCreateData(array $data,int $creatorId)
    {
        $duration = match ($data['interval']) {
            'daily'   => now()->diffInDays($data['maturity_date']),
            'weekly'  => now()->diffInWeeks($data['maturity_date']),
            'monthly' => now()->diffInMonths($data['maturity_date']),
        };
        $intervalAmount = round($data['total_amount'] / max(1, $duration), 2);
        $data['duration'] = round($duration);
        $data['interval_amount'] = $intervalAmount;
        $data['start_date'] = now();
        $data['owner_id'] = $creatorId;
        return $data;
    }

    private function generateVaultSchedule(Vault $vault): void
    {
        $startDate = $vault->start_date ?? now();

        for ($i = 1; $i <= $vault->duration; $i++) {
            $dueDate = match ($vault->interval) {
                'daily'   => $startDate->copy()->addDays($i - 1),
                'weekly'  => $startDate->copy()->addWeeks($i - 1),
                'monthly' => $startDate->copy()->addMonths($i - 1),
            };

            $vault->schedules()->create([
                'amount_due' => $vault->interval_amount,
                'due_date'   => $dueDate,
                'status'     => $i === 1
                    ? VaultScheduleStatusEnum::PAID
                    : VaultScheduleStatusEnum::PENDING,
                'paid_at'    => $i === 1 ? now() : null,
            ]);
        }
    }
    private function updateVaultOwingStatus(Vault $vault): void
    {
        $overdue = $vault->schedules()
            ->where('due_date', '<', now())
            ->where('status', VaultScheduleStatusEnum::PENDING)
            ->exists();

        $vault->update(['oweing' => $overdue]);
    }
}
