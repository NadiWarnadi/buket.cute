<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Order;
use Illuminate\Support\Facades\Config; 
use App\Observers\OrderStatusObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Order::observe(OrderStatusObserver::class);
        Config::set('midtrans.server_key', config('midtrans.server_key'));
        Config::set('midtrans.is_production', config('midtrans.is_production'));
        Config::set('midtrans.is_sanitized', config('midtrans.is_sanitized'));
        Config::set('midtrans.is_3ds', config('midtrans.is_3ds'));
        Config::set('midtrans.webhook_url', route('midtrans.webhook'));
    }
}
