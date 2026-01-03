<?php

namespace Modules\Circle\Listeners;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Circle\Events\ContributionOverdueEvent;

class ContributionOverdueListener
{
    /**
     * Create the event listener.
     */
    public function __construct() {}

    /**
     * Handle the event.
     */
    public function handle(ContributionOverdueEvent $event): void {}
}
