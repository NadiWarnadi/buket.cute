<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Contracts\Console\Kernel;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

echo "=== Testing Listener Instantiation ===\n\n";

try {
    // Test 1: Can we instantiate the listener?
    echo "1️⃣  Instantiating ProcessMessageWithFuzzyBot...\n";
    $listener = app(\App\Listeners\ProcessMessageWithFuzzyBot::class);
    echo "✅ Listener instantiated successfully\n\n";

    // Test2: Check handle method exists
    echo "2️⃣  Checking handle method...\n";
    if (method_exists($listener, 'handle')) {
        echo "✅ handle() method exists\n\n";
    } else {
        echo "❌ handle() method NOT found\n\n";
    }

    // Test 3: Check EventServiceProvider
    echo "3️⃣  Checking EventServiceProvider configuration...\n";
    $provider = app(\App\Providers\EventServiceProvider::class);
    $listenerMap = $provider->listen[\App\Events\WhatsAppMessageReceived::class] ?? [];
    echo "Listeners for WhatsAppMessageReceived: " . count($listenerMap) . "\n";
    foreach ($listenerMap as $listener) {
        echo "  - {$listener}\n";
    }
    echo "\n";

    // Test 4: Test actual event dispatch
    echo "4️⃣  Testing event dispatch on test message...\n";
    $msg = \App\Models\Message::find(148);
    if ($msg) {
        echo "Found test message ID: {$msg->id}\n";
        echo "Message body: {$msg->body}\n";
        echo "Is parsed: " . ($msg->parsed ? "YES" : "NO") . "\n\n";

        // Try dispatching
        echo "Dispatching event...\n";
        event(new \App\Events\WhatsAppMessageReceived($msg, []));
        echo "✅ Event dispatched\n";
    }

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}