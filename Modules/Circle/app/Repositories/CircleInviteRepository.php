<?php
namespace Modules\Circle\Repositories;

use App\Models\User;
use Modules\Core\Repositories\CoreRepository;
use Modules\Circle\Models\CircleInvite;
use Illuminate\Database\Eloquent\Model;
use Modules\Circle\Enums\InviteStatusEnum;
use Modules\Circle\Repositories\Contracts\CircleInviteRepositoryInterface;
use Modules\Notification\Notifications\Circle\CircleInviteNotification;

class CircleInviteRepository extends CoreRepository implements CircleInviteRepositoryInterface
{
     protected Model $model;

    public function __construct(CircleInvite $circle)
    {
        $this->model = $circle;
    }

    public function createOrRefreshInvite(array $data): CircleInvite
    {
        return $this->model->updateOrCreate(
            [
                'circle_id' => $data['circle_id'],
                'contact'   => $data['contact'],
            ],
            $data
        );
    }

    public function linkInviteToUser(User $user): void
    {
        $this->model->whereNull('invitee_id')
                ->where('contact', $user->email)
                ->where('status', InviteStatusEnum::Pending)
                ->update([
                    'invitee_id' => $user->id
                ]);
    }

    public function inAppNotifyPendingInvites(User $user): void
    {
        $this->model->where('invitee_id', $user->id)
            ->where('status', InviteStatusEnum::Pending)
            ->whereNull('notified_at')
            ->get()
            ->each(function ($invite) use ($user) {
                $user->notify(new CircleInviteNotification($invite));

                $invite->update([
                    'notified_at' => now()
                ]);
            });
    }


    public function markAccepted(CircleInvite $invite, int $userId): bool
    {
        return $invite->update([
            'status'      => 'accepted',
            'invitee_id'  => $userId,
            'accepted_at' => now(),
        ]);
    }
}
