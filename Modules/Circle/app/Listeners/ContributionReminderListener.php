<?php

namespace Modules\Circle\Listeners;

use Modules\Circle\Events\ContributionReminderEvent;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class ContributionReminderListener
{
    /**
     * Create the event listener.
     */
    public function __construct() {}

    /**
     * Handle the event.
     */
    public function handle(ContributionReminderEvent $event): void {}
}
