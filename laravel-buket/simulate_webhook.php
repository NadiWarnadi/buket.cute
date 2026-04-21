<?php
// simulate_webhook.php
// Jalankan: php simulate_webhook.php "Halo, saya mau komplain"

// Konfigurasi
$webhookUrl = 'http://localhost:8000/api/whatsapp/webhook';
$apiKey = 'your-super-secret-api-key-change-me-in-production'; // Ganti dengan nilai sebenarnya

// Ambil pesan dari CLI
if (php_sapi_name() === 'cli') {
    $message = $argv[1] ?? 'Test';
} else {
    $message = $_GET['message'] ?? 'Test';
}

// Buat payload persis seperti yang dikirim Node.js
$payload = [
    "type" => "conversation",
    "from" => "62881023926516",
    "sender_number" => "62881023926516",
    "body" => $message,
    "content" => $message,
    "isGroup" => false,
    "timestamp" => time(),
    "message_id" => "TEST_" . uniqid(),
    "pushname" => "╰(*°▽°*)╯",
    "raw_message" => [
        "key" => [
            "remoteJid" => "103963995205780@lid",
            "remoteJidAlt" => "62881023926516@s.whatsapp.net",
            "fromMe" => false,
            "id" => "TEST_" . uniqid(),
            "participant" => "",
            "addressingMode" => "lid"
        ],
        "messageTimestamp" => time(),
        "pushName" => "╰(*°▽°*)╯",
        "broadcast" => false,
        "message" => [
            "conversation" => $message,
            "messageContextInfo" => [
                "deviceListMetadata" => [
                    "senderKeyHash" => "uO2Vugbg5iZWVQ==",
                    "senderTimestamp" => "1775997358",
                    "recipientKeyHash" => "Q0UjQd08O4U98A==",
                    "recipientTimestamp" => "1776259571"
                ],
                "deviceListMetadataVersion" => 2,
                "messageSecret" => "9dha7QYMHECSKsOm4tzczOxWFIAOwlAws5vGe5NUia0="
            ]
        ]
    ]
];

// Inisialisasi cURL
$ch = curl_init($webhookUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'x-api-key: ' . $apiKey
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Tampilkan hasil
echo "=== SIMULASI WEBHOOK ===\n";
echo "Pesan dikirim: {$message}\n";
echo "HTTP Code: {$httpCode}\n";
echo "Response dari Laravel:\n";
echo $response . "\n";