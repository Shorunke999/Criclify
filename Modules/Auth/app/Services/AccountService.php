<?php

namespace Modules\Auth\Services;

use App\Enums\AccountStatus;
use App\Enums\AuditAction;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\Auth\Repositories\Contracts\AuthRepositoryInterface;
use Modules\Core\Events\AuditLogged;
use App\Traits\ResponseTrait;
use Modules\Cooperative\Enums\CooperativeStatusEnum;
use Modules\Notification\Notifications\Account\CreatorApprovedNotification;
use Modules\Notification\Notifications\Account\CreatorRejectionNotification;

class AccountService
{
    use ResponseTrait;

    public function __construct(
        protected AuthRepositoryInterface $authRepo,
    ) {}

    /* =========================================================
     | ADMIN:ACCOUNT REVIEW
     ========================================================= */

    public function getPendingCreatorAccounts()
    {
        try {
            $creators = $this->authRepo->getPendingRoles();

            return $this->success_response(
                $creators,
                'Pending accounts retrieved successfully'
            );
        } catch (Exception $e) {
            $this->reportError($e, 'Auth', [
                'action' => 'get_pending_accounts'
            ]);

            return $this->error_response(
                'Failed to retrieve pending accounts',
                500
            );
        }
    }

    public function approveCreatorAccount(int $userId)
    {
        DB::beginTransaction();

        try {
            $user = $this->authRepo->find($userId);
            $role = $user->role;
            if (! $user ||  in_array($role,['creator','cooperative'])) {
                return $this->error_response(ucfirst($role).' not found', 404);
            }

            if ($user->account_status !== AccountStatus::PENDING) {
                return $this->error_response('Account already reviewed', 422);
            }

            // Generate temporary password
            $temporaryPassword = Str::random(8);

            $user->update([
                'password' => Hash::make($temporaryPassword),
                'account_status' => AccountStatus::APPROVED,
                'email_verified_at' => now(),
            ]);
            $user->wallet()->create();
            if($role === 'cooperative'){
                $user->cooperative->status = CooperativeStatusEnum::Approved;
                $user->cooperative->save();
            }
            $auditAction = $role == 'cooperative' ? AuditAction::COOPERATIVE_APPROVED->value : AuditAction::CREATOR_APPROVED->value;
            event(new AuditLogged(
                userId: auth()->id(),
                action: $auditAction,
                entityType: 'User',
                entityId: $user->id
            ));

            $user->notify(new CreatorApprovedNotification(
                $user,
                $role,
                $temporaryPassword
            ));
            DB::commit();

            return $this->success_response(
                [],
                ucfirst($role).' account approved successfully'
            );
        } catch (Exception $e) {
            DB::rollBack();

            $this->reportError($e, 'Auth', [
                'action' => 'approve_'.$role.'_account',
                'user_id' => $userId
            ]);

            return $this->error_response(
                'Failed to approve '.$role.' account',
                500
            );
        }
    }

    public function denyCreatorAccount(int $userId, ?string $reason = null)
    {
        DB::beginTransaction();

        try {
            $user = $this->authRepo->find($userId);
            $role = $user->role;
            if (! $user ||  in_array($role,['creator','cooperative'])) {
                return $this->error_response(ucfirst($role).' not found', 404);
            }

            if ($user->account_status !== AccountStatus::PENDING) {
                return $this->error_response('Account already reviewed', 422);
            }

            $user->update([
                'account_status' => AccountStatus::DENIED,
                'reviewed_at' => now(),
            ]);

            $user->notify(new  CreatorRejectionNotification(
                $user,
                $role,
                $reason,
            ));

            DB::commit();

            return $this->success_response(
                [],
                ucfirst($role).' account rejected successfully'
            );
        } catch (Exception $e) {
            DB::rollBack();

            $this->reportError($e, 'Auth', [
                'action' => 'deny_account',
                'user_id' => $userId
            ]);

            return $this->error_response(
                'Failed to reject creator account',
                500
            );
        }
    }
}
