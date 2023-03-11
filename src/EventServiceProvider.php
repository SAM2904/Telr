<?php

namespace SudhanshuMittal\TelrGateway;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use SudhanshuMittal\TelrGateway\Events\TelrCreateRequestEvent;
use SudhanshuMittal\TelrGateway\Events\TelrRecieveTransactionResponseEvent;
use SudhanshuMittal\TelrGateway\Listeners\CreateTransactionListener;
use SudhanshuMittal\TelrGateway\Listeners\SaveTransactionResponseListener;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        TelrCreateRequestEvent::class => [
            CreateTransactionListener::class,
        ],
        // Register listener after receive response from telr
        TelrRecieveTransactionResponseEvent::class => [
            SaveTransactionResponseListener::class
        ],
    ];
}
