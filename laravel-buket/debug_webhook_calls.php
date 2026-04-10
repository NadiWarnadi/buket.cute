<?php
require_once 'vendor/autoload.php';
require_once 'bootstrap/app.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

$app = app(\Illuminate\Foundation\Application::class);
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Check the last 30 webhook log entries to see if webhook called 2x per message
$logs = DB::table('messages')->select('id', 'message_id', 'from', 'body', 'created_at', 'is_incoming')
    ->where('is_incoming', true)
    ->orderByDesc('created_at')
    ->limit(30)
    ->get();

echo "=== Last 30 Incoming Messages ===\n";
echo str_pad('DB_ID', 8) . " | " . str_pad('MSG_ID', 30) . " | " 
    . str_pad('FROM', 15) . " | " . str_pad('BODY', 40) . " | CREATED_AT\n";
echo str_repeat('-', 130) . "\n";

$messageIdCount = [];
foreach ($logs as $log) {
    if (!isset($messageIdCount[$log->message_id])) {
        $messageIdCount[$log->message_id] = 0;
    }
    $messageIdCount[$log->message_id]++;
    
    $body = substr($log->body, 0, 35);
    $createdAt = \Carbon\Carbon::parse($log->created_at)->format('H:i:s');
    echo str_pad($log->id, 8) . " | " . str_pad($log->message_id, 30) . " | " 
        . str_pad($log->from, 15) . " | " . str_pad($body, 40) . " | " . $createdAt . "\n";
}

echo "\n=== Message ID Frequency ===\n";
foreach ($messageIdCount as $msgId => $count) {
    if ($count > 1) {
        echo "⚠️  $msgId: $count times (DUPLICATE!)\n";
    } else {
        echo "✅ $msgId: $count time\n";
    }
}

// Check for duplicate message_id values within same second
echo "\n=== Checking for Webhook Calls in Same Second ===\n";
$grouped = DB::table('messages')
    ->where('is_incoming', true)
    ->selectRaw('DATE_FORMAT(created_at, "%Y-%m-%d %H:%i:%s") as second, COUNT(*) as count, GROUP_CONCAT(message_id SEPARATOR ",") as msg_ids')
    ->groupByRaw('DATE_FORMAT(created_at, "%Y-%m-%d %H:%i:%s")')
    ->having(DB::raw('COUNT(*)'), '>', 1)
    ->orderByDesc('second')
    ->limit(10)
    ->get();

if ($grouped->count() > 0) {
    echo "Found webhook calls in same second:\n";
    foreach ($grouped as $group) {
        echo "  Time: {$group->second} - {$group->count} messages\n";
        echo "  Message IDs: {$group->msg_ids}\n";
    }
} else {
    echo "No multiple webhook calls in same second found.\n";
}

// Check WhatsApp logs to see dispatcher events
echo "\n=== WhatsApp Channel Logs (Last 50) ===\n";
$logFile = storage_path('logs/whatsapp.log');
if (file_exists($logFile)) {
    $lines = array_reverse(file($logFile));
    $count = 0;
    foreach ($lines as $line) {
        if (++$count > 50) break;
        if (strpos($line, 'Incoming message processed') !== false || 
            strpos($line, 'dispatch') !== false ||
            strpos($line, 'Automatic reply sent') !== false) {
            echo trim($line) . "\n";
        }
    }
} else {
    echo "WhatsApp log file not found\n";
}
