<?php

namespace App\Services;

use App\Models\Message;
use App\Models\Customer;
use App\Models\OrderSession;
use App\Models\OutgoingMessage;
use App\Jobs\SendWhatsAppNotification;

class OrderConversationService
{
    /**
     * Process incoming message and manage order conversation flow
     */
    public static function processOrderMessage(Message $message): ?OutgoingMessage
    {
        $customer = $message->customer;
        $body = strtolower(trim($message->body));

        // Only process incoming messages
        if (!$message->is_incoming) {
            return null;
        }

        // Get or create order session
        $session = OrderSession::where('customer_id', $customer->id)
            ->where('status', OrderSession::STATUS_ACTIVE)
            ->first();

        // If no active session, check if this is order intent
        if (!$session && self::isOrderIntent($body)) {
            $session = self::createOrderSession($customer, $body);
        }

        // If still no session, return null (not order-related)
        if (!$session) {
            return null;
        }

        // Process based on current conversation step
        return self::processConversationStep($session, $message, $body);
    }

    /**
     * Check if message indicates intent to order
     */
    private static function isOrderIntent(string $message): bool
    {
        $keywords = [
            'pesan', 'order', 'mau buket', 'custom buket', 'buat pesanan',
            'ingin pesan', 'mau pesanan', 'karena', 'ulang tahun', 'anniversary',
            'valentine', 'hadiah', 'congratulations', 'ucapan', 'buket',
        ];

        foreach ($keywords as $keyword) {
            if (strpos($message, $keyword) !== false) {
                return true;
            }
        }

        // Also check if greeting first time
        if (ChatbotService::containsKeywords($message, ['halo', 'hi', 'pagi', 'siang', 'p', 'assalamu'])) {
            return true; // Assume first contact wants to order
        }

        return false;
    }

    /**
     * Create new order session
     */
    private static function createOrderSession(Customer $customer, string $initialMessage): OrderSession
    {
        $session = OrderSession::create([
            'customer_id' => $customer->id,
            'conversation_step' => OrderSession::STEP_GREETING,
            'status' => OrderSession::STATUS_ACTIVE,
            'conversation_data' => [
                'initial_message' => $initialMessage,
                'started_at' => now()->toIso8601String(),
            ],
        ]);

        return $session;
    }

    /**
     * Process conversation based on current step
     */
    private static function processConversationStep(OrderSession $session, Message $message, string $body): OutgoingMessage
    {
        $customer = $session->customer;

        switch ($session->conversation_step) {
            case OrderSession::STEP_GREETING:
            case OrderSession::STEP_WAITING_NAME:
                return self::askForName($session, $customer);

            case OrderSession::STEP_WAITING_ADDRESS:
                return self::processName($session, $customer, $body);

            case OrderSession::STEP_WAITING_PRODUCT:
                return self::processAddress($session, $customer, $body);

            case OrderSession::STEP_WAITING_REFERENCE:
                return self::processProduct($session, $customer, $body);

            case OrderSession::STEP_WAITING_DELIVERY:
                return self::processReference($session, $customer, $body);

            case OrderSession::STEP_WAITING_NOTE:
                return self::processDelivery($session, $customer, $body);

            case OrderSession::STEP_CONFIRMATION:
                return self::processNote($session, $customer, $body);

            default:
                return self::sendMessage($customer, "Ada yang ingin kami bantu? 🌸");
        }
    }

    /**
     * Step 1: Ask for name
     */
    private static function askForName(OrderSession $session, Customer $customer): OutgoingMessage
    {
        $reply = <<<'EOT'
Halo 👋 Terima kasih sudah menghubungi Buket Cute! 🌸

Kami siap membantu Anda membuat pesanan buket impian!

Sebelum kita mulai, boleh tahu nama Anda siapa?
EOT;

        $session->update(['conversation_step' => OrderSession::STEP_WAITING_NAME]);
        return self::sendMessage($customer, $reply);
    }

    /**
     * Step 2: Process name and ask for address
     */
    private static function processName(OrderSession $session, Customer $customer, string $body): OutgoingMessage
    {
        // Save name
        $name = ucwords($body);
        $session->update([
            'customer_name' => $name,
            'conversation_step' => OrderSession::STEP_WAITING_ADDRESS,
        ]);

        // Also update customer record
        if (empty($customer->name) || $customer->name === 'Customer ' . $customer->id) {
            $customer->update(['name' => $name]);
        }

        $reply = <<<'EOT'
Senang berkenalan dengan Anda, **{{name}}**! 😊

Alamat pengiriman atau penjemputan di mana?
(Lengkap dengan RT/RW, kelurahan, kecamatan)
EOT;

        $reply = str_replace('{{name}}', $name, $reply);
        return self::sendMessage($customer, $reply);
    }

    /**
     * Step 3: Process address and ask for product description
     */
    private static function processAddress(OrderSession $session, Customer $customer, string $body): OutgoingMessage
    {
        $session->update([
            'customer_address' => $body,
            'conversation_step' => OrderSession::STEP_WAITING_PRODUCT,
        ]);

        $reply = <<<'EOT'
Baik, alamat dicatat! 📍

Sekarang, buket seperti apa yang Anda inginkan?

Bisa sebutkan:
- Buat siapa? (ulang tahun, anniversary, hadiah, dll)
- Warna favorit?
- Budget range?
- Ada preferensi bunga tertentu?

Atau bisa kirim referensi gambar kalau ada! 📸
EOT;

        return self::sendMessage($customer, $reply);
    }

    /**
     * Step 4: Process product and ask for reference image (optional)
     */
    private static function processProduct(OrderSession $session, Customer $customer, string $body): OutgoingMessage
    {
        $session->update([
            'product_description' => $body,
            'conversation_step' => OrderSession::STEP_WAITING_REFERENCE,
        ]);

        $reply = <<<'EOT'
Bagus! 👍 Kami catat pesanan Anda.

Apakah Anda punya referensi gambar atau foto? 
(Kirim gambar atau bisa juga skip dengan ketik "skip" atau "tidak ada")
EOT;

        return self::sendMessage($customer, $reply);
    }

    /**
     * Step 5: Process reference image and ask for delivery type
     */
    private static function processReference(OrderSession $session, Customer $customer, string $body): OutgoingMessage
    {
        // Check if skip or not
        if ($body === 'skip' || $body === 'tidak ada') {
            $session->update(['reference_image_url' => null]);
        }

        $session->update([
            'conversation_step' => OrderSession::STEP_WAITING_DELIVERY,
        ]);

        $reply = <<<'EOT'
Baik diterima! ✓

Sekarang, bagaimana pengiriman pesanan Anda?

Pilih salah satu:
1️⃣ **Pengiriman** - Kami yang antar (ongkir sesuai area)
2️⃣ **Pickup** - Ambil sendiri di toko kami

Ketik: "pengiriman" atau "pickup"
EOT;

        return self::sendMessage($customer, $reply);
    }

    /**
     * Step 6: Process delivery type and ask for greeting note
     */
    private static function processDelivery(OrderSession $session, Customer $customer, string $body): OutgoingMessage
    {
        $deliveryType = null;
        
        if (strpos($body, 'kirim') !== false) {
            $deliveryType = 'delivery';
        } elseif (strpos($body, 'pickup') !== false || strpos($body, 'ambil') !== false) {
            $deliveryType = 'pickup';
        } else {
            return self::sendMessage($customer, 'Maaf, pilihan tidak jelas. Ketik "pengiriman" atau "pickup"');
        }

        $session->update([
            'delivery_type' => $deliveryType,
            'conversation_step' => OrderSession::STEP_WAITING_NOTE,
        ]);

        $reply = <<<'EOT'
{{delivery_text}} ✓

Apakah ingin menambahkan kartu ucapan atau kata-kata khusus?
(Bisa skip dengan ketik "skip" atau "tidak ada")
EOT;

        $deliveryText = ($deliveryType === 'delivery') 
            ? "Pilihan pengiriman dicatat!"
            : "Pickup di toko kami dicatat!";

        $reply = str_replace('{{delivery_text}}', $deliveryText, $reply);
        return self::sendMessage($customer, $reply);
    }

    /**
     * Step 7: Process note and go to confirmation
     */
    private static function processNote(OrderSession $session, Customer $customer, string $body): OutgoingMessage
    {
        if ($body === 'skip' || $body === 'tidak ada') {
            $session->update(['greeting_note' => null]);
        } else {
            $session->update(['greeting_note' => $body]);
        }

        $session->update([
            'conversation_step' => OrderSession::STEP_CONFIRMATION,
        ]);

        // Build summary
        $summary = self::buildOrderSummary($session);

        $reply = <<<'EOT'
Sempurna! 🎉 Inilah ringkasan pesanan Anda:

{{summary}}

Untuk melanjutkan, silakan:
1. Klik tombol "KONFIRMASI PESANAN" di aplikasi admin
2. Admin kami akan review dan tentukan harga
3. Pesanan akan diproses setelah konfirmasi harga

Terima kasih sudah memilih Buket Cute! 🌸
EOT;

        $reply = str_replace('{{summary}}', $summary, $reply);
        return self::sendMessage($customer, $reply);
    }

    /**
     * Build readable order summary
     */
    private static function buildOrderSummary(OrderSession $session): string
    {
        $lines = [
            "👤 *Nama*: {$session->customer_name}",
            "📍 *Alamat*: {$session->customer_address}",
            "💐 *Pesanan*: {$session->product_description}",
        ];

        if ($session->reference_image_url) {
            $lines[] = "📸 *Referensi*: Ada gambar";
        }

        $deliveryText = ($session->delivery_type === 'delivery') ? 'Dikirim' : 'Ambil di toko';
        $lines[] = "🚚 *Pengiriman*: {$deliveryText}";

        if ($session->greeting_note) {
            $lines[] = "📝 *Kartu Ucapan*: \"{$session->greeting_note}\"";
        }

        return implode("\n", $lines);
    }

    /**
     * Send message helper
     */
    private static function sendMessage(Customer $customer, string $text): OutgoingMessage
    {
        $outgoing = OutgoingMessage::create([
            'customer_id' => $customer->id,
            'to' => $customer->getWhatsAppNumber(),
            'body' => $text,
            'type' => OutgoingMessage::TYPE_TEXT,
            'status' => OutgoingMessage::STATUS_PENDING,
        ]);

        // Also store in messages table
        Message::create([
            'customer_id' => $customer->id,
            'from' => 'bot',
            'to' => $customer->phone,
            'body' => $text,
            'type' => 'text',
            'is_incoming' => false,
            'status' => 'pending',
            'parsed' => true,
        ]);

        SendWhatsAppNotification::dispatch($outgoing);

        return $outgoing;
    }
}
