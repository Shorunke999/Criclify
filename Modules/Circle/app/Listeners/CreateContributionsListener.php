<?php

namespace Modules\Circle\Listeners;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Circle\Events\CreateContributionsEvent;
use Modules\Circle\Repositories\Contracts\ContributionRepositoryInterface;

class CreateContributionsListener implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct(
        protected ContributionRepositoryInterface $contributionRepo
    ) {}

    /**
     * Handle the event.
     */
    public function handle(CreateContributionsEvent $event): void {
          $circle = $event->circle;

        // Number of cycles = number of members
        $cycles = $circle->members()->count();

        foreach ($circle->members()->cursor() as $member) {

            $this->contributionRepo->createFutureContributions(
                $circle->id,
                $member->id,
                startCycle: 0,
                cycles: $cycles
            );
        }
    }
}
