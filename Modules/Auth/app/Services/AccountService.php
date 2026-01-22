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
use Modules\Notification\Notifications\Account\CreatorApprovedNotification;
use Modules\Notification\Notifications\Account\CreatorRejectionNotification;

class AccountService
{
    use ResponseTrait;

    public function __construct(
        protected AuthRepositoryInterface $authRepo,
    ) {}

    /* =========================================================
     | ADMIN: CREATOR ACCOUNT REVIEW
     ========================================================= */

    public function getPendingCreatorAccounts()
    {
        try {
            $creators = $this->authRepo->getPendingCreators();

            return $this->success_response(
                $creators,
                'Pending creator accounts retrieved successfully'
            );
        } catch (Exception $e) {
            $this->reportError($e, 'Auth', [
                'action' => 'get_pending_creator_accounts'
            ]);

            return $this->error_response(
                'Failed to retrieve pending creator accounts',
                500
            );
        }
    }

    public function approveCreatorAccount(int $userId)
    {
        DB::beginTransaction();

        try {
            $user = $this->authRepo->find($userId);

            if (! $user || ! $user->hasRole('creator')) {
                return $this->error_response('Creator not found', 404);
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

            event(new AuditLogged(
                userId: auth()->id(),
                action: AuditAction::CREATOR_APPROVED->value,
                entityType: 'User',
                entityId: $user->id
            ));

            $user->notify(new CreatorApprovedNotification(
                $user,
                $temporaryPassword
            ));
            DB::commit();

            return $this->success_response(
                [],
                'Creator account approved successfully'
            );
        } catch (Exception $e) {
            DB::rollBack();

            $this->reportError($e, 'Auth', [
                'action' => 'approve_creator_account',
                'user_id' => $userId
            ]);

            return $this->error_response(
                'Failed to approve creator account',
                500
            );
        }
    }

    public function denyCreatorAccount(int $userId, ?string $reason = null)
    {
        DB::beginTransaction();

        try {
            $user = $this->authRepo->find($userId);

            if (! $user || ! $user->hasRole('creator')) {
                return $this->error_response('Creator not found', 404);
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
                $reason,
            ));

            DB::commit();

            return $this->success_response(
                [],
                'Creator account rejected successfully'
            );
        } catch (Exception $e) {
            DB::rollBack();

            $this->reportError($e, 'Auth', [
                'action' => 'deny_creator_account',
                'user_id' => $userId
            ]);

            return $this->error_response(
                'Failed to reject creator account',
                500
            );
        }
    }
}
