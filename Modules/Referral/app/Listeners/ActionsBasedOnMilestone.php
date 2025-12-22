<?php

namespace Modules\Referral\Listeners;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Referral\Enums\ReferralMilestone;
use Modules\Referral\Events\ReferralMilestoneReached as EventsReferralMilestoneReached;

class ActionsBasedOnMilestone
{
    /**
     * Create the event listener.
     */
    public function __construct() {}

    /**
     * Handle the event.
     */
    public function handle(EventsReferralMilestoneReached $event): void {
        foreach (ReferralMilestone::cases() as $milestone) {
            if ($event->count === $milestone->value) {
                // Send WhatsApp / Email
                // Log audit trail
            }
        }
    }
}
