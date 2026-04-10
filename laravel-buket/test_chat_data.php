<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Contracts\Console\Kernel;
use App\Models\Customer;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

echo "=== Testing Chat Data for Customer ID 3 ===\n\n";

try {
    // Get customer with ID 3
    $customer = Customer::find(3);

    if (!$customer) {
        echo "❌ Customer with ID 3 not found!\n";
        exit(1);
    }

    echo "Customer ID: {$customer->id}\n";
    echo "Customer Name: {$customer->name}\n";
    echo "Customer Phone: {$customer->phone}\n\n";

    // Load messages relationship
    $customer->load('messages');

    echo "Messages count: {$customer->messages->count()}\n\n";

    if ($customer->messages->count() > 0) {
        echo "Last 3 messages:\n";
        foreach ($customer->messages->take(-3) as $msg) {
            $direction = $msg->is_incoming ? 'INCOMING' : 'OUTGOING';
            $body = substr($msg->body ?? 'No body', 0, 50);
            echo "  [{$direction}] {$body}...\n";
        }
    } else {
        echo "❌ No messages found for this customer!\n";
    }

    echo "\n✅ Data loading successful!\n";

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}