<?php

namespace Modules\Auth\Listeners;

use App\Traits\PosthogTrait;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Auth\Events\SignupSucessfullEvent;

class SignUpEventListener
{
    use PosthogTrait;
    /**
     * Create the event listener.
     */
    public function __construct() {}

    /**
     * Handle the event.
     */
    public function handle(SignupSucessfullEvent $event): void {
         $this->capture('new_user_signup', $event->user->id, [
            'user_email' => $event->user->email
        ]);
    }
}
