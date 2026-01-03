<?php
namespace Modules\Circle\Repositories\Contracts;

use App\Models\User;
use Modules\Core\Repositories\Contracts\BaseRepositoryInterface;
use Modules\Circle\Models\CircleInvite;

interface CircleInviteRepositoryInterface extends BaseRepositoryInterface
{
    public function createOrRefreshInvite(array $data): CircleInvite;

    public function markAccepted(CircleInvite $invite, int $userId): bool;
    public function linkInviteToUser(User $user): void;
    public function inAppNotifyPendingInvites(User $user): void;

}
