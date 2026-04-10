<?php
require_once 'vendor/autoload.php';
require_once 'bootstrap/app.php';

use Illuminate\Support\Facades\DB;

$app = app(\Illuminate\Foundation\Application::class);
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Track listener invocations - look at all "Automatic reply sent" logs within 1 second 
// from single incoming message
echo "=== Analyzing Listener Invocations ===\n\n";

$logs = DB::table('messages')
    ->select('id', 'message_id', 'from', 'body', 'created_at', 'is_incoming')
    ->where('is_incoming', true)
    ->orderByDesc('created_at')
    ->limit(20)
    ->get();

foreach ($logs as $incoming) {
    $incomingTime = \Carbon\Carbon::parse($incoming->created_at);
    
    // Find all outgoing messages (bot replies) within 2 seconds of this incoming message
    $replies = DB::table('messages')
        ->select('id', 'created_at', 'body')
        ->where('is_incoming', false)
        ->where('from', env('WHATSAPP_BUSINESS_PHONE', 'system'))
        ->where('to', $incoming->from)
        ->whereBetween('created_at', [
            $incomingTime->copy()->subSeconds(1),
            $incomingTime->copy()->addSeconds(2)
        ])
        ->get();
    
    if ($replies->count() > 1) {
        echo "⚠️ DUPLICATE REPLY FOUND\n";
        echo "  Incoming Message ID: {$incoming->id} | WA_ID: {$incoming->message_id}\n";
        echo "  Body: {$incoming->body}\n";
        echo "  Incoming Time: {$incomingTime->format('H:i:s.u')}\n";
        echo "  Replies ({$replies->count()}):\n";
        
        foreach ($replies as $reply) {
            $replyTime = \Carbon\Carbon::parse($reply->created_at);
            $diff = $replyTime->diffInMilliseconds($incomingTime);
            echo "    - ID {$reply->id}: \"{$reply->body}\" at {$replyTime->format('H:i:s.u')} (+{$diff}ms)\n";
        }
        echo "\n";
    }
}

echo "\n=== Raw Log Analysis ===\n";
echo "Looking for duplicate 'Automatic reply sent' within same second...\n\n";

$logFile = storage_path('logs/whatsapp.log');
if (file_exists($logFile)) {
    $lines = file($logFile);
    $prevLine = null;
    $count = 0;
    
    foreach ($lines as $line) {
        if (strpos($line, 'Automatic reply sent') !== false) {
            if ($prevLine && strpos($prevLine, 'Automatic reply sent') !== false) {
                // Extract timestamp from line
                if (preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $line, $m1) &&
                    preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $prevLine, $m2)) {
                    if ($m1[1] === $m2[1]) {
                        echo "DUPLICATE at {$m1[1]}:\n";
                        echo "  Prev: $prevLine";
                        echo "  Curr: $line\n";
                        $count++;
                    }
                }
            }
            $prevLine = $line;
        }
    }
    
    echo "\nTotal duplicates found: $count\n";
} else {
    echo "Log file not found\n";
}
