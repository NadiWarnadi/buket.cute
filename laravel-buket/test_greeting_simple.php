<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Contracts\Console\Kernel;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

echo "=== Testing Greeting Detection (Simple) ===\n\n";

function isGreeting(string $message): bool
{
    $greetingKeywords = [
        'halo', 'hai', 'hay', 'hello', 'hey', 'hi',
        'assalamualaikum', 'asalamualaikum', 'salam',
        'pagi', 'siang', 'sore', 'malam',
        'punten', 'spada', 'selamat'
    ];

    $lowerMessage = strtolower($message);

    foreach ($greetingKeywords as $keyword) {
        if (strpos($lowerMessage, $keyword) !== false) {
            return true;
        }
    }

    return false;
}

function isOrderIntent(string $message): bool
{
    $orderKeywords = ['pesan', 'order', 'beli', 'ingin', 'mau', 'ambil', 'sewa', 'kasih', 'bikin', 'buatkan'];
    $lowerMessage = strtolower($message);

    foreach ($orderKeywords as $keyword) {
        if (strpos($lowerMessage, $keyword) !== false) {
            return true;
        }
    }

    return false;
}

try {
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
        $isGreetingResult = isGreeting($msg);
        $isOrderResult = isOrderIntent($msg);

        echo "Message: '{$msg}'\n";
        echo "  Is Greeting: " . ($isGreetingResult ? 'YES' : 'NO') . "\n";
        echo "  Is Order: " . ($isOrderResult ? 'YES' : 'NO') . "\n";
        echo "\n";
    }

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}