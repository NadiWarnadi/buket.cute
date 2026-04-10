<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Contracts\Console\Kernel;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

echo "=== Comprehensive Bot Diagnosis ===\n\n";

try {
    // 1. Check Fuzzy Rules in Database
    echo "1️⃣  FUZZY RULES CHECK\n";
    echo "========================\n";
    $rules = \App\Models\FuzzyRule::all();
    echo "Total rules: {$rules->count()}\n";
    $activeRules = \App\Models\FuzzyRule::where('is_active', true)->count();
    echo "Active rules: {$activeRules}\n";
    
    $greetingRules = \App\Models\FuzzyRule::where('intent', 'greeting')->count();
    $orderRules = \App\Models\FuzzyRule::where('intent', 'like', '%order%')->count();
    echo "Greeting rules: {$greetingRules}\n";
    echo "Order rules: {$orderRules}\n\n";

    // 2. Check WhatsApp Service
    echo "2️⃣  WHATSAPP SERVICE CHECK\n";
    echo "========================\n";
    $waService = app(\App\Services\WhatsAppService::class);
    echo "WhatsApp Service: " . ($waService ? "✅ Loaded" : "❌ Not loaded") . "\n";
    echo "WhatsApp Business Phone: " . env('WHATSAPP_BUSINESS_PHONE', 'NOT SET') . "\n";
    echo "WhatsApp API Key: " . (env('WHATSAPP_API_KEY') ? "✅ SET" : "❌ NOT SET") . "\n";
    echo "WhatsApp API URL: " . env('WHATSAPP_API_URL', 'NOT SET') . "\n\n";

    // 3. Check Services
    echo "3️⃣  SERVICES CHECK\n";
    echo "========================\n";
    $services = [
        'FuzzyBotService' => \App\Services\FuzzyBotService::class,
        'ParameterExtractionService' => \App\Services\ParameterExtractionService::class,
        'ParameterValidationService' => \App\Services\ParameterValidationService::class,
        'OrderDraftService' => \App\Services\OrderDraftService::class,
    ];

    foreach ($services as $name => $class) {
        try {
            $service = app($class);
            echo "✅ {$name}\n";
        } catch (Exception $e) {
            echo "❌ {$name}: " . $e->getMessage() . "\n";
        }
    }
    echo "\n";

    // 4. Check Event Listeners
    echo "4️⃣  EVENT LISTENERS CHECK\n";
    echo "========================\n";
    $eventServiceProvider = new \App\Providers\EventServiceProvider($app);
    echo "WhatsAppMessageReceived listeners:\n";
    foreach ($eventServiceProvider->listen[\App\Events\WhatsAppMessageReceived::class] ?? [] as $listener) {
        echo "  - {$listener}\n";
    }
    echo "\n";

    // 5. Check Recent Messages
    echo "5️⃣  RECENT MESSAGES CHECK\n";
    echo "========================\n";
    $recentMessages = \App\Models\Message::orderBy('created_at', 'desc')->take(5)->get();
    echo "Recent messages: {$recentMessages->count()}\n";
    foreach ($recentMessages as $msg) {
        $parsed = $msg->parsed ? "✅" : "❌";
        echo "  [{$parsed}] ID:{$msg->id} | From:{$msg->from} | Body:{$msg->body}\n";
    }
    echo "\n";

    // 6. Check Customers
    echo "6️⃣  CUSTOMERS CHECK\n";
    echo "========================\n";
    $customersWithMessages = \App\Models\Customer::whereHas('messages')->count();
    echo "Customers with messages: {$customersWithMessages}\n";
    echo "\n";

    // 7. Check if EventServiceProvider properly configured
    echo "7️⃣  CONFIGURATION CHECK\n";
    echo "========================\n";
    $config = config('app.debug');
    echo "Debug mode: " . ($config ? "ON" : "OFF") . "\n";
    echo "Environment: " . env('APP_ENV', 'NOT SET') . "\n\n";

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}