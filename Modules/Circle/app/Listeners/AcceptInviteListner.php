<?php

namespace Modules\Circle\Listeners;

use App\Traits\PosthogTrait;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Circle\Events\AcceptInviteEvent;

class AcceptInviteListner
{
    use PosthogTrait;
    /**
     * Create the event listener.
     */
    public function __construct() {}

    /**
     * Handle the event.
     */
    public function handle(AcceptInviteEvent $event): void {
        $this->capture(
            'accepted_circle_invite',
            $event->user->id,
            [
                'invite_id' => $event->invite->id,
                'circle_id' => $event->invite->circle_id,
            ]
        );
    }
}
