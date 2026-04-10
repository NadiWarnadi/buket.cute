<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Contracts\Console\Kernel;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

echo "=== Debugging Double Message Bug ===\n\n";

try {
    // 1. Check Event Listeners
    echo "1️⃣  EVENT LISTENERS REGISTRATION\n";
    echo "================================\n";

    $eventProvider = app(\Illuminate\Events\EventServiceProvider::class);
    if (file_exists(config_path('events.php'))) {
        $config = require config_path('events.php');
        if (isset($config[\App\Events\WhatsAppMessageReceived::class])) {
            $listeners = $config[\App\Events\WhatsAppMessageReceived::class];
            echo "Listeners in config: " . count($listeners) . "\n";
            foreach ($listeners as $listener) {
                echo "  - {$listener}\n";
            }
        }
    }
    echo "\n";

    // 2. Check ProcessMessageWithFuzzyBot listener registration
    echo "2️⃣  CHECKING LISTENER BOOT METHODS\n";
    echo "================================\n";
    if (method_exists(\App\Listeners\ProcessMessageWithFuzzyBot::class, 'shouldQueue')) {
        echo "✅ shouldQueue method exists\n";
    }
    if (method_exists(\App\Listeners\ProcessMessageWithFuzzyBot::class, 'viaConnection')) {
        echo "✅ viaConnection method exists\n";
    }
    echo "\n";

    // 3. Check recent duplicate messages
    echo "3️⃣  CHECKING FOR DUPLICATE MESSAGES\n";
    echo "================================\n";
    $duplicate = \App\Models\Message::whereRaw('created_at >= DATE_SUB(NOW(), INTERVAL 10 MINUTE)')
        ->groupBy('body', 'from', 'created_at')
        ->havingRaw('COUNT(*) > 1')
        ->get();

    $recentMessages = \App\Models\Message::orderBy('created_at', 'desc')
        ->take(10)
        ->get();

    echo "Recent 10 messages:\n";
    foreach ($recentMessages as $msg) {
        $time = $msg->created_at->format('H:i:s');
        echo "  [{$time}] ID:{$msg->id} | From:{$msg->from} | Body: " . substr($msg->body, 0, 40) . "\n";
    }
    echo "\n";

    // 4. Check if there are cached events
    echo "4️⃣  CHECKING EVENT CACHE\n";
    echo "================================\n";
    $eventCachePath = bootstrap_path('cache/events.php');
    if (file_exists($eventCachePath)) {
        echo "⚠️  Event cache exists at: {$eventCachePath}\n";
        $cached = require $eventCachePath;
        if (isset($cached[\App\Events\WhatsAppMessageReceived::class])) {
            echo "Cached listeners: " . count($cached[\App\Events\WhatsAppMessageReceived::class]) . "\n";
            foreach ($cached[\App\Events\WhatsAppMessageReceived::class] as $listener) {
                echo "  - {$listener}\n";
            }
        }
    } else {
        echo "✅ No event cache file\n";
    }
    echo "\n";

    // 5. Check EventServiceProvider
    echo "5️⃣  CHECKING EVENTSERVICEPROVIDER CONFIG\n";
    echo "================================\n";
    $listenConfig = [
        \App\Events\WhatsAppMessageReceived::class => [
            \App\Listeners\ProcessMessageWithFuzzyBot::class,
        ],
    ];
    echo "Expected listeners in EventServiceProvider:\n";
    foreach ($listenConfig[\App\Events\WhatsAppMessageReceived::class] as $idx => $listener) {
        echo "  [{$idx}] {$listener}\n";
    }

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}