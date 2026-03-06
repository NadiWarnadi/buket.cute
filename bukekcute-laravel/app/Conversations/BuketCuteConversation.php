<?php

namespace App\Conversations;

use App\Models\Customer;
use App\Models\Message as MessageModel;
use BotMan\BotMan\Messages\Outgoing\Action;
use BotMan\BotMan\Messages\Outgoing\Question;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Conversations\Conversation;

class BuketCuteConversation extends Conversation
{
    protected Customer $customer;

    /**
     * Start the conversation
     */
    public function run()
    {
        $this->askGreeting();
    }

    /**
     * Greeting and ask what customer wants
     */
    private function askGreeting()
    {
        $this->say('Halo! 👋 Selamat datang di Buket Cute 🌸');
        $this->say('Apa yang bisa kami bantu hari ini?');
        
        $this->ask('Silakan pilih:\n1️⃣ Pesan Buket\n2️⃣ Lihat Produk\n3️⃣ Info Pengiriman\n4️⃣ Cek Status Pesanan', [
            [
                'pattern' => '(pesan|order|beli|1)',
                'callback' => 'askDeliveryMethod'
            ],
            [
                'pattern' => '(produk|katalog|2)',
                'callback' => 'showProducts'
            ],
            [
                'pattern' => '(kirim|pengiriman|delivery|3)',
                'callback' => 'showShippingInfo'
            ],
            [
                'pattern' => '(status|cek|track|4)',
                'callback' => 'checkOrderStatus'
            ],
            [
                'pattern' => '.*',
                'callback' => 'default'
            ],
        ]);
    }

    /**
     * Ask delivery method - pickup or shipping
     */
    public function askDeliveryMethod()
    {
        $this->ask('Bagaimana cara Anda ingin menerima pesanan?\n\n1️⃣ Ambil di toko (Pickup)\n2️⃣ Dikirim ke rumah (Delivery)', [
            [
                'pattern' => '(ambil|pickup|toko|1)',
                'callback' => 'pickupMode'
            ],
            [
                'pattern' => '(kirim|delivery|2)',
                'callback' => 'deliveryMode'
            ],
            [
                'pattern' => '.*',
                'callback' => 'askDeliveryMethod'
            ],
        ]);
    }

    /**
     * Pickup mode
     */
    public function pickupMode()
    {
        $this->say('✅ Anda akan mengambil di toko kami.');
        $this->say('⏰ Jam Buka: Senin-Minggu 10:00-18:00');
        $this->say('📍 Lokasi: Jl. Sudirman No. 123, indramayu');
        $this->ask('Kapan Anda ingin mengambil? (Terima kasih!)', [
            [
                'pattern' => '.*',
                'callback' => 'receiveDateTime'
            ]
        ]);
    }

    /**
     * Delivery mode
     */
    public function deliveryMode()
    {
        $this->say('✅ Kami akan mengirim ke alamat Anda.');
        $this->say('⏱️ Proses: 1-2 hari kerja');
        $this->say('🚚 Pengiriman: 1-2 hari setelah proses');
        $this->say('💰 Biaya Kirim: Rp 25.000 - 35.000 (sesuai area)');
        $this->ask('Silakan berikan alamat lengkap pengiriman:', [
            [
                'pattern' => '.*',
                'callback' => 'receiveAddress'
            ]
        ]);
    }

    /**
     * Receive pickup date/time
     */
    public function receiveDateTime()
    {
        $response = $this->bot->getMessage()->getText();
        
        $this->say("Baik, ditunggu tanggal: $response");
        $this->say('Silakan lanjutkan dengan memilih produk dari katalog kami.');
        $this->showProducts();
    }

    /**
     * Receive delivery address
     */
    public function receiveAddress()
    {
        $response = $this->bot->getMessage()->getText();
        
        $this->say("✅ Alamat: $response");
        $this->say('Terima kasih! Silakan lanjutkan dengan memilih produk.');
        $this->showProducts();
    }

    /**
     * Show product catalog
     */
    public function showProducts()
    {
        $message = <<<'EOT'
🌸 Produk Kami:

1. Buket Romantis - Rp 150.000-500.000
   (Anniversary, Valentine, Proposal)

2. Buket Ulang Tahun - Rp 100.000-300.000
   (Bunga + hiasan pilihan)

3. Buket Ucapan - Rp 75.000-200.000
   (Congratulations, Get well, dll)

4. Custom Buket - Harga menyesuaikan
   (Sesuai keinginan Anda)

Tertarik dengan produk mana?
EOT;
        
        $this->say($message);
        $this->ask('Sebutkan nama produk atau nomor:', [
            [
                'pattern' => '.*',
                'callback' => 'receiveProductSelection'
            ]
        ]);
    }

    /**
     * Receive product selection
     */
    public function receiveProductSelection()
    {
        $response = $this->bot->getMessage()->getText();
        
        $this->say("✅ Produk: $response");
        $this->ask('Berapa jumlah?', [
            [
                'pattern' => '.*',
                'callback' => 'receiveQuantity'
            ]
        ]);
    }

    /**
     * Receive quantity
     */
    public function receiveQuantity()
    {
        $response = $this->bot->getMessage()->getText();
        
        $this->say("✅ Jumlah: $response");
        $this->say('Terima kasih! Pesanan Anda akan kami proses. Admin akan menghubungi Anda dalam beberapa saat.');
        $this->askDefault();
    }

    /**
     * Show shipping info
     */
    public function showShippingInfo()
    {
        $message = <<<'EOT'
🚚 Informasi Pengiriman:

📍 Area Kirim: Jakarta & Sekitarnya
⏱️ Waktu Proses: 1-2 hari kerja
⏱️ Waktu Pengiriman: 1-2 hari setelah proses

💰 Biaya Kirim:
   • Jakarta Pusat/Barat/Timur/Utara/Selatan: Rp 25.000-30.000
   • Bekasi/Tangerang: Rp 35.000

Ada pertanyaan lain?
EOT;
        
        $this->say($message);
        $this->askDefault();
    }

    /**
     * Check order status
     */
    public function checkOrderStatus()
    {
        $this->say('Untuk mengecek status pesanan, silakan hubungi admin kami atau tanya di sini 😊');
        $this->askDefault();
    }

    /**
     * Default - ask what else can we help
     */
    public function default()
    {
        $this->askDefault();
    }

    /**
     * Default question
     */
    private function askDefault()
    {
        $this->ask('Ada yang bisa dibantu lagi?\n1️⃣ Pesan Buket\n2️⃣ Lihat Produk\n3️⃣ Info Pengiriman', [
            [
                'pattern' => '(pesan|order|1)',
                'callback' => 'askDeliveryMethod'
            ],
            [
                'pattern' => '(produk|2)',
                'callback' => 'showProducts'
            ],
            [
                'pattern' => '(kirim|3)',
                'callback' => 'showShippingInfo'
            ],
            [
                'pattern' => '(bye|dadah|exit|tidak)',
                'callback' => 'sayGoodbye'
            ],
            [
                'pattern' => '.*',
                'callback' => 'default'
            ],
        ]);
    }

    /**
     * Say goodbye
     */
    public function sayGoodbye()
    {
        $this->say('Terima kasih telah memilih Buket Cute 🌸');
        $this->say('Sampai jumpa! 👋');
    }
}
