<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Contracts\Console\Kernel;
use App\Listeners\ProcessMessageWithFuzzyBot;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

echo "=== Testing Greeting Detection ===\n\n";

try {
    // Create mock listener to test isGreeting method
    $listener = new ProcessMessageWithFuzzyBot();

    // Test messages
    $testMessages = [
        'Halo',
        'hai kak',
        'hello',
        'assalamualaikum',
        'selamat pagi',
        'punten',
        'saya mau pesan',
        'bantuan',
    ];

    foreach ($testMessages as $msg) {
        $isGreeting = $listener->isGreeting($msg);
        $isOrder = $listener->isOrderIntent($msg);

        echo "Message: '{$msg}'\n";
        echo "  Is Greeting: " . ($isGreeting ? 'YES' : 'NO') . "\n";
        echo "  Is Order: " . ($isOrder ? 'YES' : 'NO') . "\n";
        echo "\n";
    }

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}