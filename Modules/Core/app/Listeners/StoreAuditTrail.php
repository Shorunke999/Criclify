<?php

namespace Modules\Core\Listeners;

use Modules\Core\Repositories\AuditTrail\AuditTrailRepository;
use Illuminate\Support\Facades\Request;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Core\Events\AuditLogged;

class StoreAuditTrail implements ShouldQueue
{
    use InteractsWithQueue;
   public function __construct(
        protected AuditTrailRepository $repository
    ) {}

    public function handle(AuditLogged $event): void
    {
        $this->repository->create([
            'user_id'     => $event->userId,
            'action'      => $event->action,
            'entity_type' => $event->entityType,
            'entity_id'   => $event->entityId,
            'metadata'    => $event->metadata,
            'version'     => $event->version,
            'ip_address'  => Request::ip(),
            'user_agent'  => Request::userAgent(),
        ]);
    }
}
