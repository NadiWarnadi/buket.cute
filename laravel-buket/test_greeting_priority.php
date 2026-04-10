<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Contracts\Console\Kernel;
use App\Models\Message;
use App\Models\Customer;
use App\Models\OrderDraft;
use App\Events\WhatsAppMessageReceived;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

echo "=== Testing Greeting Priority Over Active Draft ===\n\n";

try {
    // Get customer
    $customer = Customer::find(3);
    if (!$customer) {
        echo "❌ Customer not found!\n";
        exit(1);
    }

    echo "Customer: {$customer->name} ({$customer->phone})\n\n";

    // Create a test active draft
    $draft = OrderDraft::create([
        'customer_id' => $customer->id,
        'data' => [
            'customer_name' => 'Test User',
            'product_name' => 'Test Product',
            'quantity' => 1,
        ],
        'step' => 'confirming',
        'expires_at' => now()->addHours(1),
    ]);

    echo "✅ Created test draft ID: {$draft->id} with step: {$draft->step}\n\n";

    // Create greeting message
    $message = Message::create([
        'customer_id' => $customer->id,
        'message_id' => 'test_greeting_priority_' . time(),
        'from' => $customer->phone,
        'to' => 'system',
        'body' => 'Halo',
        'type' => 'text',
        'status' => 'received',
        'is_incoming' => true,
        'parsed' => false,
        'chat_status' => 'active',
    ]);

    echo "Created greeting message: '{$message->body}'\n\n";

    // Fire the event
    echo "Firing WhatsAppMessageReceived event...\n";
    \Illuminate\Support\Facades\Event::dispatch(new WhatsAppMessageReceived($message));

    echo "✅ Event dispatched!\n\n";

    // Check if draft was cancelled
    $draftAfter = OrderDraft::find($draft->id);
    if ($draftAfter) {
        echo "❌ Draft still exists after greeting!\n";
    } else {
        echo "✅ Draft cancelled due to greeting priority\n";
    }

    // Check customer context
    $customer->refresh();
    echo "Customer context after greeting: '{$customer->current_context}'\n\n";

    // Check replies
    $replies = Message::where('customer_id', $customer->id)
        ->where('is_incoming', false)
        ->where('created_at', '>=', now()->subMinutes(2))
        ->orderBy('created_at', 'desc')
        ->take(3)
        ->get();

    echo "Bot replies in last 2 minutes: {$replies->count()}\n";
    foreach ($replies as $reply) {
        echo "  - Reply: " . substr($reply->body, 0, 100) . "...\n";
    }

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}