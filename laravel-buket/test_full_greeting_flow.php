<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Contracts\Console\Kernel;
use App\Models\Message;
use App\Models\Customer;
use App\Events\WhatsAppMessageReceived;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

echo "=== Testing Full Greeting Flow ===\n\n";

try {
    // Get customer with active draft
    $customer = Customer::find(3);
    if (!$customer) {
        echo "❌ Customer not found!\n";
        exit(1);
    }

    echo "Customer: {$customer->name} ({$customer->phone})\n";
    echo "Current Context: {$customer->current_context}\n\n";

    // Check active draft
    $draftService = app(\App\Services\OrderDraftService::class);
    $activeDraft = $draftService->getCustomerActiveDraft($customer);

    if ($activeDraft) {
        echo "Active Draft ID: {$activeDraft->id}, Step: {$activeDraft->step}\n\n";
    } else {
        echo "No active draft\n\n";
    }

    // Create test message "Halo"
    $message = Message::create([
        'customer_id' => $customer->id,
        'message_id' => 'test_greeting_' . time(),
        'from' => $customer->phone,
        'to' => 'system',
        'body' => 'Halo',
        'type' => 'text',
        'status' => 'received',
        'is_incoming' => true,
        'parsed' => false,
        'chat_status' => 'active',
    ]);

    echo "Created test message ID: {$message->id}\n";
    echo "Message body: {$message->body}\n\n";

    // Fire the event
    echo "Firing MessageReceived event...\n";
    \Illuminate\Support\Facades\Event::dispatch(new WhatsAppMessageReceived($message));

    echo "✅ Event dispatched!\n\n";

    // Check if draft was cancelled
    $activeDraftAfter = $draftService->getCustomerActiveDraft($customer);
    if ($activeDraftAfter) {
        echo "❌ Draft still active after greeting!\n";
    } else {
        echo "✅ Draft cancelled due to greeting\n";
    }

    // Check customer context
    $customer->refresh();
    echo "Customer context after greeting: '{$customer->current_context}'\n\n";

    // Check replies
    $replies = Message::where('customer_id', $customer->id)
        ->where('is_incoming', false)
        ->where('created_at', '>=', now()->subMinutes(2))
        ->get();

    echo "Bot replies in last 2 minutes: {$replies->count()}\n";
    foreach ($replies as $reply) {
        echo "  - Reply: " . substr($reply->body, 0, 100) . "...\n";
    }

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}