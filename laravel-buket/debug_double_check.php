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
    // 1. Check Event Cache
    echo "1️⃣  CHECKING EVENT CACHE\n";
    echo "================================\n";
    $eventCachePath = __DIR__ . '/bootstrap/cache/events.php';
    if (file_exists($eventCachePath)) {
        echo "⚠️  Event cache exists!\n";
        $cached = require $eventCachePath;
        if (isset($cached[\App\Events\WhatsAppMessageReceived::class])) {
            $listeners = $cached[\App\Events\WhatsAppMessageReceived::class];
            echo "Listeners in cache: " . count($listeners) . "\n";
            foreach ($listeners as $listener) {
                echo "  - {$listener}\n";
            }
        }
    } else {
        echo "✅ No event cache file\n";
    }
    echo "\n";

    // 2. Check EventServiceProvider directly
    echo "2️⃣  CHECKING EVENTSERVICEPROVIDER LISTEN CONFIG\n";
    echo "================================\n";
    $providerFile = file_get_contents(__DIR__ . '/app/Providers/EventServiceProvider.php');
    if (strpos($providerFile, 'ProcessMessageWithFuzzyBot::class') !== false) {
        echo "✅ ProcessMessageWithFuzzyBot found in EventServiceProvider\n";
        $count = substr_count($providerFile, 'ProcessMessageWithFuzzyBot::class');
        echo "   Occurrences: {$count}\n";
    }
    echo "\n";

    // 3. Check recent messages with same ID
    echo "3️⃣  CHECKING FOR DUPLICATE MESSAGE IDs\n";
    echo "================================\n";
    $recentMessages = \App\Models\Message::orderBy('id', 'desc')
        ->take(20)
        ->get();

    echo "Recent 20 messages:\n";
    $lastId = null;
    $duplicates = [];
    foreach ($recentMessages as $msg) {
        $time = $msg->created_at->format('H:i:s');
        echo "  ID:{$msg->id} [{$time}] From:{$msg->from} Body: " . substr($msg->body, 0, 35);
        if ($lastId && $msg->body === $recentMessages->firstWhere('id', $lastId)->body ?? null) {
            echo " ⚠️ DUPLICATE";
            $duplicates[] = $msg->id;
        }
        echo "\n";
        $lastId = $msg->id;
    }

    if (count($duplicates) > 0) {
        echo "\n❌ Found duplicate message bodies: " . implode(', ', $duplicates) . "\n";
    }
    echo "\n";

    // 4. Check if webhook is being called multiple times
    echo "4️⃣  CHECKING WEBHOOK LOGS\n";
    echo "================================\n";
    $webhookLog2 = shell_exec("powershell \"Get-Content " . __DIR__ . "/storage/logs/whatsapp.log | Select-Object -Last 5 | Select-String 'Incoming message'\"");
    echo "Recent incoming messages in log:\n{$webhookLog2}\n";

    // 5. Check message creation flow
    echo "5️⃣  CHECKING MESSAGE CREATION\n";
    echo "================================\n";
    $lastMsg = \App\Models\Message::orderBy('created_at', 'desc')->first();
    if ($lastMsg) {
        echo "Last message:\n";
        echo "  ID: {$lastMsg->id}\n";
        echo "  Body: {$lastMsg->body}\n";
        echo "  Created: {$lastMsg->created_at}\n";
        echo "  Parsed: " . ($lastMsg->parsed ? "YES" : "NO") . "\n";

        // Check if there are recent messages with same body/from
        $similar = \App\Models\Message::where('body', $lastMsg->body)
            ->where('from', $lastMsg->from)
            ->where('created_at', '>=', now()->subMinutes(5))
            ->count();

        echo "  Similar messages in last 5 min: {$similar}\n";
    }

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}