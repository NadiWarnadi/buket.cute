<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Contracts\Console\Kernel;
use App\Services\FuzzyBotService;
use App\Models\Customer;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

echo "=== Testing Order Confirmation Logic ===\n\n";

try {
    $customer = Customer::find(3);
    if (!$customer) {
        echo "❌ Customer not found!\n";
        exit(1);
    }

    $fuzzyBot = new FuzzyBotService();

    // Test dengan "Halo" (yang dikirim user)
    $testMessage = "Halo";
    echo "Testing message: '{$testMessage}'\n";

    $result = $fuzzyBot->processOrderConfirmation($testMessage, $customer);
    echo "Result: " . json_encode($result, JSON_PRETTY_PRINT) . "\n\n";

    // Test dengan "ya" (yang seharusnya match)
    $testMessage2 = "ya";
    echo "Testing message: '{$testMessage2}'\n";

    $result2 = $fuzzyBot->processOrderConfirmation($testMessage2, $customer);
    echo "Result: " . json_encode($result2, JSON_PRETTY_PRINT) . "\n\n";

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}