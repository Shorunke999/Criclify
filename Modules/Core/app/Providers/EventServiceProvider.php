<?php

namespace Modules\Core\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Core\Events\AuditLogged as EventsAuditLogged;
use Modules\Core\Listeners\StoreAuditTrail as ListenersStoreAuditTrail;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        EventsAuditLogged::class => [
            ListenersStoreAuditTrail::class,
        ],
    ];
}
