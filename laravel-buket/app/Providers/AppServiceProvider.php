<?php

namespace App\Providers;

use App\Services\Chatbot\IntentClassifier;
use App\Services\Chatbot\OrderFlowManager;
use App\Services\Chatbot\ProductMatcher;
use App\Services\Chatbot\ReplySender;
use App\Services\OrderDraftService;
use App\Services\ParameterExtractionService;
use App\Services\ParameterValidationService;
use App\Services\WhatsAppService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // IntentClassifier
        $this->app->singleton(IntentClassifier::class, function ($app) {
            return new IntentClassifier();
        });

        // ReplySender
        $this->app->singleton(ReplySender::class, function ($app) {
            return new ReplySender($app->make(WhatsAppService::class));
        });

        // ProductMatcher
        $this->app->singleton(ProductMatcher::class, function ($app) {
            return new ProductMatcher();
        });

        // OrderFlowManager (binding dengan parameter tambahan 'conv')
        $this->app->bind(OrderFlowManager::class, function ($app, $params) {
            return new OrderFlowManager(
                $params['conv'],
                $app->make(OrderDraftService::class),
                $app->make(ProductMatcher::class),
                $app->make(ParameterExtractionService::class),
                $app->make(ParameterValidationService::class),
                $app->make(ReplySender::class)
            );
        });

        // Jika OrderDraftService, ParameterExtractionService, dll belum terdaftar,
        // Laravel akan otomatis menyelesaikannya via reflection (zero configuration).
        // Tapi kita bisa mendaftarkannya secara eksplisit jika perlu.
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}