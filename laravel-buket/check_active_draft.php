<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Contracts\Console\Kernel;
use App\Models\Customer;
use App\Services\OrderDraftService;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

echo "=== Checking Active Draft for Customer 3 ===\n\n";

try {
    $customer = Customer::find(3);
    if (!$customer) {
        echo "❌ Customer not found!\n";
        exit(1);
    }

    echo "Customer: {$customer->name} ({$customer->phone})\n";
    echo "Current Context: {$customer->current_context}\n\n";

    // Check active drafts
    $draftService = new OrderDraftService(new \App\Services\ParameterValidationService());
    $activeDraft = $draftService->getCustomerActiveDraft($customer);

    if ($activeDraft) {
        echo "❌ ACTIVE DRAFT FOUND!\n";
        echo "Draft ID: {$activeDraft->id}\n";
        echo "Step: {$activeDraft->step}\n";
        echo "Expires: {$activeDraft->expires_at}\n";
        echo "Data: " . json_encode($activeDraft->data, JSON_PRETTY_PRINT) . "\n";
        echo "\nThis is why greeting is not processed - active draft takes priority!\n";
    } else {
        echo "✅ No active draft found\n";
        echo "Greeting should be processed normally\n";
    }

} catch (Exception $e) {
        echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}