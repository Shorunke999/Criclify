<?php

namespace Modules\Circle\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Circle\Events\AcceptInviteEvent;
use Modules\Circle\Events\CircleCreatedEvent;
use Modules\Circle\Events\ContributionOverdueEvent;
use Modules\Circle\Events\ContributionReminderEvent;
use Modules\Circle\Events\MemberJoinedEvent;
use Modules\Circle\Events\SendCircleInvite;
use Modules\Circle\Listeners\AcceptInviteListner;
use Modules\Circle\Listeners\CircleCreatedListner;
use Modules\Circle\Listeners\ContributionOverdueListener;
use Modules\Circle\Listeners\ContributionReminderListener;
use Modules\Circle\Listeners\MemberJoinedListner;
use Modules\Circle\Listeners\SendCircleInviteListner;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array<string, array<int, string>>
     */
    protected $listen = [
        CircleCreatedEvent::class => [
            CircleCreatedListner::class
        ],
        MemberJoinedEvent::class => [
            MemberJoinedListner::class
        ],
        SendCircleInvite::class => [
            SendCircleInviteListner::class
        ],
        AcceptInviteEvent::class =>[
            AcceptInviteListner::class
        ],
        ContributionOverdueEvent::class => [
            ContributionOverdueListener::class
        ],
        ContributionReminderEvent::class => [
            ContributionReminderListener::class
        ]
    ];

    /**
     * Indicates if events should be discovered.
     *
     * @var bool
     */
    protected static $shouldDiscoverEvents = true;

    /**
     * Configure the proper event listeners for email verification.
     */
    protected function configureEmailVerification(): void {}
}
