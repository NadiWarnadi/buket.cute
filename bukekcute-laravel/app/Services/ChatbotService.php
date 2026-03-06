<?php

namespace App\Services;

use App\Models\Message;
use App\Models\Customer;
use App\Models\OutgoingMessage;
use App\Jobs\SendWhatsAppNotification;

class ChatbotService
{
    /**
     * Process message and determine if auto-reply should be sent
     */
    public static function processMessage(Message $message): ?OutgoingMessage
    {
        $customer = $message->customer;
        $body = strtolower(trim($message->body));

        // Only reply to incoming messages from customer
        if (!$message->is_incoming) {
            return null;
        }

        // Prevent replying to bot's own messages
        if (strtolower($message->from) === 'bot' || $message->from === 'bot@whatsapp') {
            return null;
        }

        // Already replied?
        if ($message->parsed) {
            return null;
        }

        // Extract customer name if message contains name info
        self::extractAndUpdateCustomerName($customer, $message->body);

        // Get or create auto-reply for general inquiries
        $reply = self::getAutoReply($body, $customer, $message);

        if ($reply) {
            return self::sendAutoReply($customer, $reply);
        }

        return null;
    }

    /**
     * Try to extract customer name from message
     * E.g: "Halo nama saya Nadi" or "Saya Nadi" -> extract name
     */
    private static function extractAndUpdateCustomerName(Customer $customer, string $body)
    {
        // Pattern 1: "nama saya XYZ" or "nama aku XYZ"
        if (preg_match('/nama\s+(?:saya|aku)\s+([a-zA-Z\s]+?)(?:\s|$)/i', $body, $matches)) {
            $name = trim($matches[1]);
            if (strlen($name) > 1 && strlen($name) < 50) {
                $customer->update(['name' => $name]);
                \Log::info("📝 Customer name updated: $name");
            }
        }
        // Pattern 2: "saya XYZ" (at beginning)
        elseif (preg_match('/^saya\s+([a-zA-Z\s]+?)(?:\s|$)/i', $body, $matches)) {
            $name = trim($matches[1]);
            if (strlen($name) > 1 && strlen($name) < 50) {
                $customer->update(['name' => $name]);
                \Log::info("📝 Customer name updated: $name");
            }
        }
        // Pattern 3: "Ini XYZ" / "nama ku XYZ"
        elseif (preg_match('/(?:ini|nama\s+ku|aku)\s+([a-zA-Z\s]+?)(?:\s|$)/i', $body, $matches)) {
            $name = trim($matches[1]);
            if (strlen($name) > 1 && strlen($name) < 50) {
                $customer->update(['name' => $name]);
                \Log::info("📝 Customer name updated: $name");
            }
        }
    }

    /**
     * Determine auto-reply based on message content
     */
    private static function getAutoReply(string $messageBody, Customer $customer, Message $message): ?string
    {
        // Greeting/Sales Opening
        if (self::containsKeywords($messageBody, ['halo', 'hi', 'hey', 'p', 'pagi', 'siang', 'malam', 'alo', 'permisi', 'assalamu', 'hm', 'assalamualaikum'])) {
            return self::getGreetingReply($customer);
        }

        // Ask about products
        if (self::containsKeywords($messageBody, ['produk', 'katalog', 'menu', 'apa aja', 'harga', 'berapa', 'jenis', 'pilihan'])) {
            return self::getProductReply();
        }

        // Pesan buket - ask delivery method
        if (self::containsKeywords($messageBody, ['pesan', 'order', 'beli', 'ingin', 'mau', 'butuh', 'belis'])) {
            return self::getDeliveryMethodQuestion();
        }

        // Pickup answer
        if (self::containsKeywords($messageBody, ['ambil', 'pickup', 'toko', 'datang', 'ke toko'])) {
            return "✅ Baik! Anda ingin ambil di toko.\n\n⏰ Jam Buka: Senin-Minggu 10:00-18:00\n📍 Lokasi: Jl. Sudirman No. 123, Jakarta\n\nKapan Anda ingin ambil? Silakan sebutkan tanggal & jam 📅";
        }

        // Shipping/Delivery answer
        if (self::containsKeywords($messageBody, ['kirim', 'delivery', 'dikirim', 'ongkir', 'ongkos kirim', 'pengiriman'])) {
            return self::getShippingReply();
        }

        // Status/Tracking
        if (self::containsKeywords($messageBody, ['status', 'track', 'pesanan', 'cek', 'kapan selesai', 'ready'])) {
            return self::getStatusAskReply($customer);
        }

        // Confirmation
        if (self::containsKeywords($messageBody, ['terima kasih', 'makasih', 'thanks', 'ok', 'baik', 'siap', 'sepakat'])) {
            return "Terima kasih! 🙏 Pesanan Anda akan kami proses. Ada yang bisa dibantu lagi? 😊";
        }

        // Goodbye
        if (self::containsKeywords($messageBody, ['bye', 'dadah', 'sampai jumpa', 'dalu', 'goodbye'])) {
            return "Sampai jumpa! Terima kasih memilih Buket Cute 🌸";
        }

        return null;
    }

    /**
     * Ask customer delivery method at start
     */
    private static function getDeliveryMethodQuestion(): string
    {
        return <<<'EOT'
Halo! 👋 Terima kasih telah memilih Buket Cute 🌸

Anda ingin pesan buket? Mantap!

Pertanyaan: Bagaimana cara Anda ingin menerima pesanan?

1️⃣ Ambil di toko (Pickup)
2️⃣ Dikirim ke rumah (Delivery)

Silakan balas "ambil" atau "kirim" 📍
EOT;
    }

    /**
     * Shipping/delivery info - with address question
     */
    private static function getShippingReply(): string
    {
        return <<<'EOT'
🚚 Baik, kami akan kirim ke alamat Anda!

📍 Area Kirim: Jakarta & Sekitarnya
⏱️ Proses: 1-2 hari kerja
⏱️ Pengiriman: 1-2 hari setelah proses

💰 Biaya Kirim:
   • Jakarta: Rp 25.000-30.000
   • Bekasi/Tangerang: Rp 35.000

📝 Silakan berikan:
1. Alamat lengkap
2. Nama penerima
3. HP penerima (jika beda)
EOT;
    }

    /**
     * Greeting reply - personalized with customer name
     */
    private static function getGreetingReply(Customer $customer): string
    {
        $hour = now()->hour;
        
        if ($hour < 12) {
            $greeting = "Pagi";
        } elseif ($hour < 15) {
            $greeting = "Siang";
        } elseif ($hour < 18) {
            $greeting = "Sore";
        } else {
            $greeting = "Malam";
        }

        // Use real customer name if available and not default
        $customerName = $customer->name;
        if (str_contains($customerName, 'Customer') || empty($customerName)) {
            $customerDisplayName = 'Semuanya';
        } else {
            $customerDisplayName = $customerName;
        }

        return "Halo $customerDisplayName! 👋\n$greeting Kami dari Buket Cute 🌸\n\nApakah Anda ingin memesan buket? Silakan ketik \"pesan\" atau tanyakan apapun tentang produk kami! 😊";
    }

    /**
     * Product info reply
     */
    private static function getProductReply(): string
    {
        return <<<'EOT'
🌸 Produk Kami:

1. Buket Romantis - Rp 150.000-500.000
   (Anniversary, Valentine, Proposal)

2. Buket Ulang Tahun - Rp 100.000-300.000
   (Bunga + hiasan pilihan)

3. Buket Ucapan - Rp 75.000-200.000
   (Congratulations, Get well, dll)

4. Custom Buket - Harga menyesuaikan
   (Sesuai keinginan Anda)

Tertarik? Sebutkan:
- Jenis buket
- Jumlah
- Alamat pengiriman/pickup
EOT;
    }

    /**
     * Ask for order status
     */
    private static function getStatusAskReply(Customer $customer): string
    {
        $lastOrder = $customer->latestOrder;
        
        if ($lastOrder) {
            $status = self::getOrderStatusText($lastOrder->status);
            return "📦 Status pesanan terbaru Anda: *$status*\n\nAda pertanyaan? Admin kami siap membantu! 😊";
        }

        return "Sepertinya Anda belum punya pesanan. Mau pesan buket sekarang? 🌸";
    }

    /**
     * Get readable order status
     */
    private static function getOrderStatusText(string $status): string
    {
        return match($status) {
            'pending' => 'Pending (Menunggu Konfirmasi)',
            'confirmed' => 'Confirmed (Dikonfirmasi)',
            'processing' => 'Processing (Sedang Dikerjakan)',
            'ready' => 'Ready (Siap Dikirim)',
            'shipped' => 'Shipped (Dalam Perjalanan)',
            'delivered' => 'Delivered (Terkirim)',
            'completed' => 'Completed (Selesai)',
            'cancelled' => 'Cancelled (Dibatalkan)',
            default => ucfirst($status),
        };
    }

    /**
     * Check if message contains keywords
     */
    public static function containsKeywords(string $message, array $keywords): bool
    {
        foreach ($keywords as $keyword) {
            if (strpos($message, strtolower($keyword)) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Send auto-reply message
     */
    private static function sendAutoReply(Customer $customer, string $replyText): OutgoingMessage
    {
        // Store outgoing message
        $outgoing = OutgoingMessage::create([
            'customer_id' => $customer->id,
            'to' => $customer->getWhatsAppNumber(),
            'body' => $replyText,
            'type' => OutgoingMessage::TYPE_TEXT,
            'status' => OutgoingMessage::STATUS_PENDING,
        ]);

        // Also store copy in messages table
        Message::create([
            'customer_id' => $customer->id,
            'from' => 'bot',
            'to' => $customer->phone,
            'body' => $replyText,
            'type' => 'text',
            'is_incoming' => false,
            'status' => 'pending',
            'parsed' => true,
        ]);

        // Queue for sending
        SendWhatsAppNotification::dispatch($outgoing);

        return $outgoing;
    }
}
