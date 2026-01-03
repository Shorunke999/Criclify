<?php

namespace Modules\Circle\Listeners;

use App\Traits\PosthogTrait;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Circle\Events\MemberJoinedEvent;

class MemberJoinedListner
{
    use PosthogTrait;
    /**
     * Create the event listener.
     */
    public function __construct() {}

    /**
     * Handle the event.
     */
    public function handle(MemberJoinedEvent $event): void {
        $this->capture('member_joined_circle', $event->member->user_id, [
            'circle_id' => $event->member->circle_id,
            'member_id' => $event->member->id,
            'joined_at' => $event->member->created_at->toDateTimeString(),
        ]);
    }
}
