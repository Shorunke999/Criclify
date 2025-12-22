<?php

namespace Modules\Core\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AuditLogged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public readonly ?int $userId,
        public readonly string $action,
        public readonly ?string $entityType = null,
        public readonly ?int $entityId = null,
        public readonly array $metadata = [],
        public readonly ?string $version = null
    ) {}
}
