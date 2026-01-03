<?php

namespace Modules\Waitlist\Listeners;

use App\Traits\PosthogTrait;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Waitlist\Events\WaitlistAddedEvent;

class WaitlistAddedEventListener
{
    use PosthogTrait;
    /**
     * Create the event listener.
     */
    public function __construct() {}

    /**
     * Handle the event.
     */
    public function handle(WaitlistAddedEvent $event): void {
        $this->capture('new_waitlist_added', null,[
            'waitlist' =>[
                'email' => $event->entry->email,
                'name' => $event->entry->name
            ]
        ]);
    }
}
