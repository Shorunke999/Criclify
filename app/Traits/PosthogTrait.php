<?php

namespace App\Traits;

use PostHog\PostHog;

trait PosthogTrait
{
    public function capture(
        string $event,
        ?int $userId = null,
        array $properties = []
    ): void {
        if (!config('services.posthog.enabled')) {
            return;
        }

        PostHog::capture([
            'distinctId' => $userId ? "user_{$userId}" : 'anonymous',
            'event' => $event,
            'properties' => $properties,
        ]);
    }
}
