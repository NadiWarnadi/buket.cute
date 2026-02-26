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

        // Only reply to incoming messages
        if (!$message->is_incoming) {
            return null;
        }

        // Get or create auto-reply
        $reply = self::getAutoReply($body, $customer);

        if ($reply) {
            return self::sendAutoReply($customer, $reply);
        }

        return null;
    }

    /**
     * Determine auto-reply based on message content
     */
    private static function getAutoReply(string $messageBody, Customer $customer): ?string
    {
        // Greeting/Sales Opening - offer product info
        if (self::containsKeywords($messageBody, ['halo', 'hi', 'hey', 'p', 'pagi', 'siang', 'malam', 'alo', 'permisi', 'assalamu', 'hm'])) {
            return self::getGreetingReply($customer);
        }

        // Ask about products/katalog
        if (self::containsKeywords($messageBody, ['produk', 'katalog', 'menu', 'apa aja', 'pilihan', 'harga', 'berapa', 'diskon', 'promo'])) {
            return self::getProductReply();
        }

        // Ask about delivery/shipping
        if (self::containsKeywords($messageBody, ['pengiriman', 'ongkir', 'biaya kirim', 'berapa hari', 'sampai', 'dikirim', 'deliver'])) {
            return self::getShippingReply();
        }

        // Ask about status - order tracking
        if (self::containsKeywords($messageBody, ['status', 'track', 'cek pesanan', 'kapan ready', 'besok', 'selesai', 'jadi', 'pesan saya'])) {
            return self::getStatusAskReply($customer);
        }

        // Thank you / Confirmation
        if (self::containsKeywords($messageBody, ['terima kasih', 'thanks', 'makasih', 'ok oke', 'baik', 'siap', 'sepakat', 'setuju'])) {
            return "Terima kasih sudah memesan! 🙏 Kami akan segera memproses pesanan Anda. Ada yang bisa kami bantu lagi?";
        }

        // Farewell
        if (self::containsKeywords($messageBody, ['bye', 'dadah', 'sampai jumpa', 'nanti', 'dulu', 'bye bye', 'goodbye'])) {
            return "Sampai jumpa! Terima kasih telah memilih Buket Cute 🌸";
        }

        return null;
    }

    /**
     * Greeting reply
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

        return "Halo {$customer->name}! 👋 $greeting Kami dari Buket Cute. Apa yang bisa kami bantu? Silakan pilih produk atau tanyakan apapun tentang buket kami! 🌸";
    }

    /**
     * Product info reply
     */
    private static function getProductReply(): string
    {
        return <<<'EOT'
🌸 Produk Kami:

1. **Buket Romantis** - Rp 150.000-500.000
   Cocok untuk: Anniversary, Valentine, Proposal

2. **Buket Ulang Tahun** - Rp 100.000-300.000
   Terdiri dari: Bunga pilihan + hiasan unik

3. **Buket Ucapan** - Rp 75.000-200.000
   Untuk: Congratulations, Get well, dll

4. **Custom Buket** - Harga menyesuaikan
   Sesuai keinginan kamu!

Tertarik dengan yang mana? Silakan sebutkan jumlah dan alamat pengiriman 📍
EOT;
    }

    /**
     * Shipping/delivery info
     */
    private static function getShippingReply(): string
    {
        return <<<'EOT'
🚚 Informasi Pengiriman:

📍 Area Kirim: Jakarta & Sekitarnya
⏱️ Waktu Proses: 1-2 hari kerja
💰 Biaya Kirim: 
   • Area Jakarta Pusat/Selatan: Rp 25.000
   • Area Jakarta Timur/Barat/Utara: Rp 30.000
   • Bekasi/Tangerang: Rp 35.000

Mau pesan sekarang? Silakan sebutkan:
1. Jenis buket
2. Jumlah
3. Alamat lengkap
4. Nama penerima (opsional)
EOT;
    }

    /**
     * Ask for order status - link to see existing orders
     */
    private static function getStatusAskReply(Customer $customer): string
    {
        $lastOrder = $customer->latestOrder;
        
        if ($lastOrder) {
            $status = self::getOrderStatusText($lastOrder->status);
            return "Status pesanan terakhir Anda: *$status* 📦\n\nUntuk info lebih lengkap, silakan hubungi admin kami atau klik link: admin akan segera membantu!";
        }

        return "Sepertinya Anda belum punya pesanan sebelumnya. Mau pesan buket sekarang? 🌸";
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
    private static function containsKeywords(string $message, array $keywords): bool
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
