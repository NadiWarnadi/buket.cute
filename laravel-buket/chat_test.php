<?php
/**
 * WhatsApp Webhook Simulator - Database Monitoring Mode
 * Payload tetap sama, tapi jawaban diambil dari database Laravel
 */

// --- 1. SETTING DATABASE (Cek di file .env Laravel Anda) ---
$db_host = '127.0.0.1';
$db_name = 'chat-buket'; // Ganti dengan nama DB di .env
$db_user = 'root';
$db_pass = '';

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
} catch (PDOException $e) {
    die("Gagal Koneksi Database: " . $e->getMessage() . "\nPastikan setting DB di script ini sama dengan .env Laravel.\n");
}

// --- 2. SETTING API ---
$webhookKey = 'your-super-secret-api-key-change-me-in-production'; 
$laravelWebhookUrl = 'http://localhost:8000/api/whatsapp/webhook';
$phoneTester = "628878336711";

echo "--- WA Chat Simulator (Authorized + DB Reader) ---\n";
echo "Ketik pesan untuk bot. Ketik 'exit' untuk keluar.\n\n";

while (true) {
    echo "User: ";
    $message = trim(fgets(STDIN));

    if ($message === 'exit') break;
    if ($message === '') continue;

    // Payload lengkap sesuai WebhookController Anda
    $payload = [
        "type" => "text",
        "from" => $phoneTester,
        "sender_number" => $phoneTester,
        "body" => $message,
        "content" => $message,
        "isGroup" => false,
        "timestamp" => time(),
        "message_id" => "TEST-" . uniqid(),
        "pushname" => "Tester User",
    ];

    $ch = curl_init($laravelWebhookUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'X-Api-Key: ' . $webhookKey, 
        'Accept: application/json'
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode >= 200 && $httpCode < 300) {
        echo "Sistem: Pesan diterima Laravel. Menunggu balasan bot...\n";
        
        // Tunggu 2 detik agar Laravel & ChatbotService selesai memproses fuzzy
        sleep(2); 

        // Cari pesan balasan terbaru di tabel messages untuk nomor ini
        // is_incoming = 0 artinya pesan keluar (dari Bot ke User)
        $stmt = $pdo->prepare("
            SELECT body FROM messages 
            WHERE `to` = :phone 
            AND is_incoming = 0 
            ORDER BY id DESC LIMIT 1
        ");
        $stmt->execute(['phone' => $phoneTester]);
        $reply = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($reply) {
            echo " > [BOT]: " . $reply['body'] . "\n";
        } else {
            echo " > [BOT]: (Belum ada balasan tersimpan di database)\n";
        }
    } else {
        echo "Sistem Error (HTTP $httpCode): " . $response . "\n";
    }
    echo "\n";
}
