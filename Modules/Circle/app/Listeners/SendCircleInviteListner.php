<?php

namespace Modules\Circle\Listeners;

use App\Traits\PosthogTrait;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Circle\Events\SendCircleInvite;
use Illuminate\Support\Facades\Mail;
use Modules\Circle\Mail\CircleInviteMail;
use Modules\Notification\Emails\Circle\CircleInviteMail as CircleCircleInviteMail;
use Modules\Notification\Notifications\Circle\CircleInviteNotification;

class SendCircleInviteListner
{
    use PosthogTrait;
    /**
     * Create the event listener.
     */
    public function __construct() {}

    /**
     * Handle the event.
     */
    public function handle(SendCircleInvite $event): void {

        Mail::to($event->invite->contact)
            ->queue(new CircleCircleInviteMail($event->circle, $event->invite));

        if ($event->existingUser) {
            $event->existingUser->notify(new CircleInviteNotification($event->invite));
        }

        $this->capture('circle_invite_sent', $event->invite->inviter_id, [
            'circle_id' => $event->circle->id,
            'invitee_email' => $event->invite->email,
            'invitee_user_id' => $event->existingUser ? $event->existingUser->id : null,
            'invite_sent_at' => $event->invite->created_at->toDateTimeString(),
        ]);
    }
}
