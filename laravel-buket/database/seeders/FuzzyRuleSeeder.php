<?php

namespace Database\Seeders;

use App\Models\FuzzyRule;
use Illuminate\Database\Seeder;

class FuzzyRuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rules = [
            // Greeting intents
            [
                'intent' => 'greeting',
                'pattern' => 'halo|halo|hey|hi|assalamualaikum|pagi|sore|malam',
                'confidence_threshold' => 0.5,
                'action' => 'reply',
                'response_template' => 'Halo! 👋 Ada yang bisa saya bantu?',
                'is_active' => true,
            ],
            // Inquiry about product
            [
                'intent' => 'inquiry_product',
                'pattern' => 'berapa harga|harga|produk apa saja|menu|ada apa aja|punya apa',
                'confidence_threshold' => 0.55,
                'action' => 'reply',
                'response_template' => 'Silakan lihat katalog produk kami. Kami menjual berbagai jenis bukrt custom. Apa yang Anda cari?',
                'is_active' => true,
            ],
            // Order status inquiry
            [
                'intent' => 'inquiry_order_status',
                'pattern' => 'pesan|order|status|kapan siap|berapa lama|sudah siap',
                'confidence_threshold' => 0.6,
                'action' => 'escalate',
                'response_template' => 'Baik, saya akan cek status pesanan Anda. Bisa tunggu sebentar?',
                'is_active' => true,
            ],
            // Custom design inquiry
            [
                'intent' => 'inquiry_custom',
                'pattern' => 'custom|design|sesuai|khusus|tema|karakter',
                'confidence_threshold' => 0.5,
                'action' => 'reply',
                'response_template' => 'Kami siap membuat kue custom sesuai keinginan Anda! Ceritakan desain apa yang Anda inginkan.',
                'is_active' => true,
            ],
            // Payment inquiry
            [
                'intent' => 'inquiry_payment',
                'pattern' => 'bayar|pembayaran|harga akhir|total|invoice|transfer',
                'confidence_threshold' => 0.55,
                'action' => 'escalate',
                'response_template' => 'Saya akan mengirimkan detail pembayaran untuk Anda. Silakan tunggu.',
                'is_active' => true,
            ],
            // Complain
            [
                'intent' => 'complaint',
                'pattern' => 'komplain|keluh|tidak puas|masalah|error|salah|rusak',
                'confidence_threshold' => 0.6,
                'action' => 'escalate',
                'response_template' => 'Maaf atas ketidaknyamanannya. Saya akan terhubungkan dengan tim kami untuk mengatasi masalah ini.',
                'is_active' => true,
            ],
            // Closing/Goodbye
            [
                'intent' => 'closing',
                'pattern' => 'terima kasih|thanks|bye|bye bye|dadah|sampai jumpa',
                'confidence_threshold' => 0.5,
                'action' => 'reply',
                'response_template' => 'Terima kasih sudah menghubungi kami! 😊 Sampai jumpa lagi!',
                'is_active' => true,
            ],
        ];

        foreach ($rules as $rule) {
            FuzzyRule::updateOrCreate(
                ['intent' => $rule['intent']],
                $rule
            );
        }
    }
}
