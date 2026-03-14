<?php

namespace Botble\RezgoConnector\Providers;

use Botble\Ecommerce\Events\OrderPlacedEvent;
use Botble\RezgoConnector\Listeners\OrderPlacedListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     */
    protected $listen = [
        OrderPlacedEvent::class => [
            OrderPlacedListener::class,
        ],
    ];
}
