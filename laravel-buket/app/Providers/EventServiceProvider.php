<?php

namespace App\Providers;

use App\Events\WhatsAppMessageReceived;
use App\Listeners\ProcessMessageWithFuzzyBot;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use App\Models\Order;
use App\Observers\OrderObserver;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        WhatsAppMessageReceived::class => [
            ProcessMessageWithFuzzyBot::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
            Order::observe(OrderObserver::class);


    }
}
