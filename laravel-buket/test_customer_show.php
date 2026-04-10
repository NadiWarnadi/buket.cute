<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Contracts\Console\Kernel;
use App\Models\Customer;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

echo "=== Testing Customer Show Method ===\n\n";

try {
    // Get customer with ID 3
    $customer = Customer::find(3);

    if (!$customer) {
        echo "Customer with ID 3 not found. Creating test customer...\n";
        $customer = Customer::create([
            'name' => 'Test Customer',
            'phone' => '081234567890',
            'address' => 'Test Address'
        ]);
        echo "Created customer ID: {$customer->id}\n";
    }

    echo "Customer ID: {$customer->id}\n";
    echo "Customer Name: {$customer->name}\n";
    echo "Customer Phone: {$customer->phone}\n\n";

    // Test loading relationships (same as in controller)
    echo "Loading relationships...\n";
    $customer->load(['orders', 'messages', 'orderDrafts']);

    echo "Orders count: {$customer->orders->count()}\n";
    echo "Messages count: {$customer->messages->count()}\n";
    echo "Order Drafts count: {$customer->orderDrafts->count()}\n\n";

    echo "✅ SUCCESS: No relationship errors!\n";

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}