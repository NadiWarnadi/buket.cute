<?php
/**
 * Debug Script untuk test koneksi Laravel ↔ Node.js
 * Run: php debug-connection.php
 */

require 'vendor/autoload.php';

// Load .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$waServiceUrl = env('WHATSAPP_SERVICE_URL');
$apiKey = env('WHATSAPP_API_KEY');
$webhookKey = env('WHATSAPP_WEBHOOK_KEY');

echo "\n╔════════════════════════════════════════════════════════════╗\n";
echo "║        🔍 DEBUG CONNECTION: Laravel ↔ Node.js           ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

// 1. Check Environment
echo "📋 Environment Check:\n";
echo "   WHATSAPP_SERVICE_URL: {$waServiceUrl}\n";
echo "   WHATSAPP_API_KEY: " . substr($apiKey, 0, 10) . "...\n";
echo "   WHATSAPP_WEBHOOK_KEY: " . substr($webhookKey, 0, 10) . "...\n";
echo "   Keys Match: " . (($apiKey === $webhookKey) ? "✅ YES" : "❌ NO") . "\n\n";

// 2. Test Node.js Health Endpoint
echo "🔗 Testing Node.js /health endpoint (no auth required):\n";
try {
    $client = new \GuzzleHttp\Client();
    $response = $client->get("{$waServiceUrl}/health", ['timeout' => 5]);
    echo "   Status: {$response->getStatusCode()}\n";
    echo "   Body: {$response->getBody()}\n";
    echo "   ✅ Node.js is responding!\n\n";
} catch (\Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
    echo "   ❌ Make sure Node.js is running on port 3000\n\n";
}

// 3. Test Status Endpoint (with auth)
echo "🔐 Testing Node.js /api/status endpoint (with auth):\n";
try {
    $client = new \GuzzleHttp\Client();
    $response = $client->get("{$waServiceUrl}/api/status", [
        'headers' => ['x-api-key' => $apiKey],
        'timeout' => 5
    ]);
    echo "   Status: {$response->getStatusCode()}\n";
    $body = json_decode($response->getBody(), true);
    echo "   Body: " . json_encode($body, JSON_PRETTY_PRINT) . "\n";
    echo "   ✅ Authentication working!\n\n";
} catch (\GuzzleHttp\Exception\ClientException $e) {
    if ($e->getResponse()->getStatusCode() === 401) {
        echo "   ❌ Error: 401 Unauthorized\n";
        echo "   Check: API_KEY di Node.js .env\n";
        echo "   Check: WHATSAPP_API_KEY di Laravel .env\n";
    } else {
        echo "   ❌ Error: " . $e->getMessage() . "\n";
    }
    echo "\n";
} catch (\Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n\n";
}

// 4. Simulate Webhook (Node.js → Laravel)
echo "📨 Simulating Webhook from Node.js to Laravel:\n";
try {
    $client = new \GuzzleHttp\Client();
    $payload = [
        'sender_number' => '6283824665074',
        'message_type' => 'text',
        'body' => 'Test message dari debug script',
        'timestamp' => time(),
        'message_id' => 'test_' . time(),
        'type' => 'text'
    ];
    
    $response = $client->post('http://127.0.0.1:8000/api/whatsapp/webhook', [
        'headers' => [
            'x-api-key' => $webhookKey,
            'Content-Type' => 'application/json'
        ],
        'json' => $payload,
        'timeout' => 5
    ]);
    
    echo "   Status: {$response->getStatusCode()}\n";
    $body = json_decode($response->getBody(), true);
    echo "   Response: " . json_encode($body, JSON_PRETTY_PRINT) . "\n";
    echo "   ✅ Webhook endpoint working!\n\n";
} catch (\GuzzleHttp\Exception\ClientException $e) {
    if ($e->getResponse()->getStatusCode() === 401) {
        echo "   ❌ Error: 401 Unauthorized at Laravel webhook\n";
        echo "   Check: WHATSAPP_WEBHOOK_KEY di Laravel .env\n";
    } else {
        echo "   ❌ Error: " . $e->getMessage() . "\n";
        echo "   Response: " . $e->getResponse()->getBody() . "\n";
    }
    echo "\n";
} catch (\Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n\n";
}

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║                    Debug Complete                         ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";
?>
