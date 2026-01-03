<?php

namespace Modules\Circle\Listeners;

use App\Traits\PosthogTrait;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Circle\Events\CircleCreatedEvent;

class CircleCreatedListner
{
    use PosthogTrait;
    /**
     * Create the event listener.
     */
    public function __construct() {}

    /**
     * Handle the event.
     */
    public function handle(CircleCreatedEvent $event): void {

        $this->capture('circle_created', $event->circle->creator_id, [
            'circle_id' => $event->circle->id,
            'max_members' => $event->circle->limit,
            'contribution_amount' => $event->circle->amount,
            'positioning_method' => $event->circle->positioning_method,
        ]);

    }
}
