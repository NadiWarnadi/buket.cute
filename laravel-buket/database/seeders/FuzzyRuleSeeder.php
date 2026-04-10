<?php

namespace Database\Seeders;

use App\Models\FuzzyRule;
use Illuminate\Database\Seeder;

class FuzzyRuleSeeder extends Seeder
{
    public function run(): void
    {
        // Kosongkan tabel dulu agar tidak duplikat saat testing
        FuzzyRule::truncate();

        $rules = [
            // ========== 1. ORDER FLOW (PRIORITY TERTINGGI) ==========
            [
                'intent' => 'order_start',
                'pattern' => 'pesan|order|beli|buatkan|pesen|mau beli|mau pesan|mohon pesan|order dong|order buket|bikin buket|request buket|booking|pesan buket|beli buket|mau order',
                'confidence_threshold' => 0.7,
                'action' => 'order',
                'response_template' => null,
                'context_slug' => null,
                'next_context' => 'collecting_order',
                'priority' => 100,
                'is_active' => true,
            ],
            [
                'intent' => 'confirm_yes',
                'pattern' => 'iya|ya|ok|oke|okee|siap|benar|betul|lanjut|confirm|jadi|yup|yoi|oke siap|udah bener|deal|sip|iya deh|ya deh|oke deh',
                'confidence_threshold' => 0.8,
                'action' => 'confirm_order',
                'response_template' => null,
                'context_slug' => 'confirming',
                'next_context' => 'order_completed',
                'priority' => 90,
                'is_active' => true,
            ],
            [
                'intent' => 'confirm_no',
                'pattern' => 'tidak|nggak|ngga|gak|batal|cancel|salah|ubah|ganti|jangan|nope|tidak jadi|ulang|ga jadi|ga mau',
                'confidence_threshold' => 0.8,
                'action' => 'reply',
                'response_template' => 'Siap ka, pesanan dibatalkan. Mari kita mulai ulang ya. Boleh tau nama Kakak siapa?',
                'context_slug' => 'confirming',
                'next_context' => 'collecting_name',
                'priority' => 90,
                'is_active' => true,
            ],

            // ========== 2. PRODUK & KATALOG (DIIMPROVE) ==========
            [
                'intent' => 'show_catalog',
                'pattern' => 'katalog|lihat produk|daftar produk|pilihan buket|menu|ready apa aja|contoh buket|pilihan bunga|liat liat|produk apa|ada apa|apa saja|lihat katalog|cek produk|produknya apa',
                'confidence_threshold' => 0.6,
                'action' => 'show_product',
                'response_template' => null, // Will be generated dynamically from database
                'context_slug' => null,
                'next_context' => null,
                'priority' => 70,
                'is_active' => true,
            ],
            [
                'intent' => 'product_mawar',
                'pattern' => 'buket mawar|mawar|buket bunga mawar|rose bouquet|mawar merah|mawar putih|mawar pink|mawar kuning',
                'confidence_threshold' => 0.7,
                'action' => 'reply',
                'response_template' => 'Buket Mawar kami ada berbagai warna: Merah, Putih, Pink, Kuning. Harga mulai Rp 75rb. Mau yang mana ka?',
                'context_slug' => null,
                'next_context' => 'collecting_order',
                'priority' => 65,
                'is_active' => true,
            ],
            [
                'intent' => 'product_snack',
                'pattern' => 'snack bouquet|bouquet snack|buket snack|snack|makanan|kue|chocolate|permen|snack box',
                'confidence_threshold' => 0.7,
                'action' => 'reply',
                'response_template' => 'Snack Bouquet kami berisi berbagai camilan dan bunga cantik. Harga mulai Rp 100rb. Cocok untuk surprise!',
                'context_slug' => null,
                'next_context' => 'collecting_order',
                'priority' => 65,
                'is_active' => true,
            ],
            [
                'intent' => 'product_hamper',
                'pattern' => 'hamper|parcel|paket|gift box|kado|hadiah|surprise box|hamper ulang tahun',
                'confidence_threshold' => 0.7,
                'action' => 'reply',
                'response_template' => 'Hampers kami berisi bunga + snack premium. Harga mulai Rp 150rb. Perfect untuk ulang tahun atau anniversary!',
                'context_slug' => null,
                'next_context' => 'collecting_order',
                'priority' => 65,
                'is_active' => true,
            ],
            [
                'intent' => 'product_wisuda',
                'pattern' => 'wisuda|graduation|bouquet wisuda|buket wisuda|selamat wisuda|tamatan|kuliah selesai',
                'confidence_threshold' => 0.7,
                'action' => 'reply',
                'response_template' => 'Bouquet Wisuda kami spesial dengan bunga dan ucapan selamat. Harga mulai Rp 125rb. Ada berbagai ukuran!',
                'context_slug' => null,
                'next_context' => 'collecting_order',
                'priority' => 65,
                'is_active' => true,
            ],
            [
                'intent' => 'price_inquiry',
                'pattern' => 'harga|berapa|harganya|pricelist|biaya|berapaan|ongkir|cek harga|mahal ga|price|murah ga|range harga',
                'confidence_threshold' => 0.7,
                'action' => 'reply',
                'response_template' => 'Harga buket kami bervariasi mulai dari Rp 50rb - 500rb tergantung ukuran dan jenis bunga. Untuk detailnya kakak bisa cek di Katalog ya!',
                'context_slug' => null,
                'next_context' => null,
                'priority' => 60,
                'is_active' => true,
            ],

            // ========== 3. PARAMETER COLLECTION (BARU) ==========
            [
                'intent' => 'provide_name',
                'pattern' => 'nama saya|nma|aku|gue|gue adalah|saya adalah|namaku|nama ku',
                'confidence_threshold' => 0.8,
                'action' => 'collect_name',
                'response_template' => null,
                'context_slug' => 'collecting_name',
                'next_context' => 'collecting_product',
                'priority' => 85,
                'is_active' => true,
            ],
            [
                'intent' => 'provide_address',
                'pattern' => 'alamat|tinggal di|di|rumah|lokasi|tempat tinggal|kirim ke|antar ke',
                'confidence_threshold' => 0.7,
                'action' => 'collect_address',
                'response_template' => null,
                'context_slug' => 'collecting_address',
                'next_context' => 'collecting_quantity',
                'priority' => 85,
                'is_active' => true,
            ],
            [
                'intent' => 'provide_quantity',
                'pattern' => 'biji|buket|pcs|buah|tangkai|ikat|set|piece|qty|jumlah',
                'confidence_threshold' => 0.6,
                'action' => 'collect_quantity',
                'response_template' => null,
                'context_slug' => 'collecting_quantity',
                'next_context' => 'confirming',
                'priority' => 85,
                'is_active' => true,
            ],

            // ========== 4. GREETING & HELP ==========
            [
                'intent' => 'greeting',
                'pattern' => 'halo|hai|hay|hello|hey|hi|assalamualaikum|asalamualaikum|salam|pagi|siang|sore|malam|p|punten|spada|selamat datang',
                'confidence_threshold' => 0.7,
                'action' => 'reply',
                'response_template' => 'Halo ka! 👋 Selamat datang di Buket Cute Indramayu. Mau cari buket untuk wisuda, ultah, atau nikahan? Ketik "Pesan" untuk order ya!',
                'context_slug' => null,
                'next_context' => null,
                'priority' => 40,
                'is_active' => true,
            ],
            [
                'intent' => 'help',
                'pattern' => 'bantuan|tolong|cara|gimana|help|panduan|info|tanya|bingung|confused|tidak paham|ga paham',
                'confidence_threshold' => 0.6,
                'action' => 'reply',
                'response_template' => "Butuh bantuan? 😊 Ini cara ordernya:\n1️⃣ Ketik 'Katalog' untuk lihat produk\n2️⃣ Ketik 'Pesan' untuk mulai order\n3️⃣ Ikuti instruksi bot sampai selesai\n4️⃣ Konfirmasi pembayaran\n\nMau mulai sekarang?",
                'context_slug' => null,
                'next_context' => null,
                'priority' => 30,
                'is_active' => true,
            ],

            // ========== 5. ADMIN & COMPLAINT ==========
            [
                'intent' => 'escalate_admin',
                'pattern' => 'admin|operator|manusia|komplain|masalah|rusak|operator|panggil admin|hubungi admin|bicara admin|bicara orang',
                'confidence_threshold' => 0.7,
                'action' => 'escalate',
                'response_template' => 'Baik ka, pesan Kakak segera diteruskan ke Admin kami. Mohon tunggu sebentar ya.',
                'context_slug' => null,
                'next_context' => null,
                'priority' => 80,
                'is_active' => true,
            ],

            // ========== 6. CLOSING ==========
            [
                'intent' => 'closing',
                'pattern' => 'terima kasih|thanks|makasih|nuhun|syukron|ok tks|thank you|dah|bye|bye bye|sampai jumpa|dadah',
                'confidence_threshold' => 0.7,
                'action' => 'reply',
                'response_template' => 'Sama-sama ka! Senang bisa melayani Kakak. Ditunggu kabar baiknya! ✨',
                'context_slug' => null,
                'next_context' => null,
                'priority' => 20,
                'is_active' => true,
            ],

            // ========== 7. FALLBACK (PASIF) ==========
            [
                'intent' => 'default_fallback',
                'pattern' => 'bot_internal_fallback_logic',
                'confidence_threshold' => 1.0, 
                'action' => 'manual_review',
                'response_template' => 'Maaf ka, saya belum paham maksudnya. Bisa ketik "Bantuan" untuk melihat instruksi?',
                'context_slug' => null,
                'next_context' => null,
                'priority' => 1,
                'is_active' => true,
            ],
        ];

        foreach ($rules as $rule) {
            FuzzyRule::create($rule);
        }

        $this->command->info('✅ Fuzzy Rules Seeder Berhasil Dijalankan!');
    }
}
