<?php
require_once 'vendor/autoload.php';
require_once 'bootstrap/app.php';

use App\Models\Message;
use App\Models\Customer;
use App\Events\WhatsAppMessageReceived;
use Illuminate\Support\Facades\Log;

$app = app(\Illuminate\Foundation\Application::class);
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Testing Listener Invocation ===\n";

// Create test customer
$customer = Customer::firstOrCreate(
    ['phone' => '62881023926516'],
    ['name' => 'Test Customer']
);

// Create test message (incoming)
$testMessage = Message::create([
    'customer_id' => $customer->id,
    'message_id' => 'trace_test_' . time(),
    'from' => '62881023926516',
    'to' => env('WHATSAPP_BUSINESS_PHONE', 'system'),
    'body' => 'Test trace message',
    'type' => 'text',
    'status' => 'pending',
    'is_incoming' => true,
    'parsed' => false,
    'chat_status' => 'active',
]);

echo "Test message created: ID {$testMessage->id}\n";
echo "Dispatching WhatsAppMessageReceived event...\n\n";

// Dispatch event (this will trigger listener)
WhatsAppMessageReceived::dispatch($testMessage, [
    'type' => 'text',
    'body' => 'Test trace message',
    'from' => '62881023926516'
]);

echo "\nEvent dispatched. Checking logs...\n\n";

// Wait briefly for listener to process
sleep(1);

// Read logs to find trace IDs for this message
$logFile = storage_path('logs/whatsapp.log');
if (file_exists($logFile)) {
    $lines = array_reverse(file($logFile));
    $traceIds = [];
    $foundMessage = false;
    
    foreach ($lines as $line) {
        if (strpos($line, 'trace_test_') !== false) {
            $foundMessage = true;
            echo "Found reference to test message:\n$line\n";
        }
        
        if ($foundMessage && preg_match('/"trace_id":"([^"]+)"/', $line, $m)) {
            echo "Trace ID: {$m[1]}\n";
            $traceIds[] = $m[1];
        }
        
        if ($foundMessage && count($traceIds) >= 2) {
            break;
        }
    }
    
    echo "\n=== Trace ID Analysis ===\n";
    echo "Total trace IDs found: " . count($traceIds) . "\n";
    if (count($traceIds) > 1) {
        if (count(array_unique($traceIds)) === 1) {
            echo "⚠️  SAME trace ID appears multiple times = Listener called multiple times for same message!\n";
        } else {
            echo "Different trace IDs = Listener called once, but multiple responses generated\n";
        }
    } else {
        echo "✅ Only 1 trace ID = Listener called once only\n";
    }
    
    // Count bot replies for this message
    $replies = Message::where('to', '62881023926516')
        ->where('is_incoming', false)
        ->where('from', env('WHATSAPP_BUSINESS_PHONE', 'system'))
        ->orderByDesc('created_at')
        ->limit(5)
        ->get();
    
    echo "\n=== Bot Replies ===\n";
    foreach ($replies as $reply) {
        echo "- ID {$reply->id}: {$reply->body}\n";
    }
    
} else {
    echo "Log file not found\n";
}
