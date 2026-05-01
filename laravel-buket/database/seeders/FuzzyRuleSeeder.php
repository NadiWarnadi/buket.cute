<?php

namespace Database\Seeders;

use App\Models\FuzzyRule;
use Illuminate\Database\Seeder;

class FuzzyRuleSeeder extends Seeder
{
    public function run(): void
    {
        // Kosongkan tabel dulu agar tidak duplikat
        FuzzyRule::truncate();

        $rules = [
            // ========== GLOBAL INTENT (dipantau di semua state) ==========
            [
                'intent' => 'ORDER_FROM_WEB',
                'pattern' => 'Saya ingin pesan:',
                'confidence_threshold' => 0.9,
                'action' => 'ORDER_FROM_WEB',
                'response_template' => null,
                'context_slug' => 'intent_global',
                'next_context' => null,
                'priority' => 98,
                'is_active' => true,
            ],
            [
                'intent' => 'CEK_STATUS',
                'pattern' => 'cek status|lacak|tracking|status pesanan|dimana pesanan|kurir',
                'confidence_threshold' => 0.8,
                'action' => 'CEK_STATUS',
                'response_template' => null,
                'context_slug' => 'intent_global',
                'next_context' => '8',
                'priority' => 95,
                'is_active' => true,
            ],
            [
                'intent' => 'KOMPLAIN',
                'pattern' => 'komplain|keluhan|kecewa|tidak sesuai|rusak|admin|bantuan',
                'confidence_threshold' => 0.8,
                'action' => 'KOMPLAIN',
                'response_template' => null,
                'context_slug' => 'intent_global',
                'next_context' => '9',
                'priority' => 95,
                'is_active' => true,
            ],

            // ========== ORDER FLOW (PRIORITY TERTINGGI) ==========
            [
                'intent' => 'order_start',
                'pattern' => 'pesan|order|beli|buatkan|mau beli|mau pesan|bikin buket|request buket|booking|pesan buket',
                'confidence_threshold' => 0.75,
                'action' => 'reply',
                'response_template' => 'Baik, Kak. Siapa nama Kakak?',
                'context_slug' => 'new_order',
                'next_context' => '2',
                'priority' => 90,
                'is_active' => true,
            ],
            [
                'intent' => 'confirm_yes',
                'pattern' => 'iya|ya|ok|oke|siap|benar|betul|lanjut|confirm|jadi|yoi|deal|sip',
                'confidence_threshold' => 0.85,
                'action' => 'confirm_order',
                'context_slug' => 'confirming',
                'next_context' => '7',
                'priority' => 85,
                'is_active' => true,
            ],
            [
                'intent' => 'confirm_no',
                'pattern' => 'tidak|nggak|gak|batal|cancel|salah|ubah|ganti|jangan|ulang',
                'confidence_threshold' => 0.85,
                'action' => 'restart_collection',
                'response_template' => 'Baik, kita mulai ulang. Siapa nama Kakak?',
                'context_slug' => 'confirming',
                'next_context' => '2',
                'priority' => 85,
                'is_active' => true,
            ],

            // ========== PRODUK & KATALOG ==========
            [
                'intent' => 'show_catalog',
                'pattern' => 'katalog|lihat produk|daftar produk|menu|contoh buket|pilihan bunga|liat liat|produk apa|cek produk',
                'confidence_threshold' => 0.7,
                'action' => 'show_product',
                'context_slug' => null,
                'priority' => 70,
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

            // ========== PARAMETER COLLECTION ==========
            [
                'intent' => 'provide_name',
                'pattern' => 'nama saya|aku|gue|saya adalah|namaku|nama ku|panggil saya',
                'confidence_threshold' => 0.8,
                'action' => 'collect_name',
                'context_slug' => 'collecting_name',
                'next_context' => '3',
                'priority' => 80,
                'is_active' => true,
            ],
            [
                'intent' => 'provide_quantity',
                'pattern' => 'biji|buket|pcs|buah|tangkai|ikat|set|piece|qty|jumlah',
                'confidence_threshold' => 0.7,
                'action' => 'collect_quantity',
                'context_slug' => 'collecting_quantity',
                'next_context' => '5',
                'priority' => 80,
                'is_active' => true,
            ],
            [
                'intent' => 'provide_address',
                'pattern' => 'alamat|tinggal di|di|rumah|lokasi|kirim ke|antar ke',
                'confidence_threshold' => 0.7,
                'action' => 'collect_address',
                'context_slug' => 'collecting_address',
                'next_context' => '6',
                'priority' => 80,
                'is_active' => true,
            ],

            // ========== GREETING & HELP ==========
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

            // ========== CLOSING ==========
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

            // ========== FALLBACK ==========
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