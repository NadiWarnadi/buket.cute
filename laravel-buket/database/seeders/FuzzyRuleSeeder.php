<?php

namespace Database\Seeders;

use App\Models\FuzzyRule;
use Illuminate\Database\Seeder;

class FuzzyRuleSeeder extends Seeder
{
    public function run(): void
    {
        $rules = [
            [
                'intent' => 'greeting',
                'pattern' => 'halo|halo|hey|hi|assalamualaikum|pagi|sore|malam',
                'confidence_threshold' => 0.5,
                'action' => 'reply',
                'response_template' => 'Halo! 👋 Ada yang bisa saya bantu?',
                'context_slug' => null,
                'next_context' => 'initial_inquiry',
                'priority' => 1,
                'is_active' => true,
            ],
            [
                'intent' => 'inquiry_product',
                'pattern' => 'berapa harga|harga|produk apa saja|menu|ada apa aja|punya apa',
                'confidence_threshold' => 0.55,
                'action' => 'reply',
                'response_template' => 'Silakan lihat katalog produk kami. Kami menjual berbagai jenis buket custom. Apa yang Anda cari?',
                'context_slug' => 'initial_inquiry',
                'next_context' => 'product_selection',
                'priority' => 2,
                'is_active' => true,
            ],
            [
                'intent' => 'inquiry_order_status',
                'pattern' => 'pesan|order|status|kapan siap|berapa lama|sudah siap',
                'confidence_threshold' => 0.6,
                'action' => 'escalate',
                'response_template' => 'Baik, saya akan cek status pesanan Anda. Bisa tunggu sebentar?',
                'context_slug' => null,
                'next_context' => 'checking_status',
                'priority' => 3,
                'is_active' => true,
            ],
            [
                'intent' => 'inquiry_custom',
                'pattern' => 'custom|design|sesuai|khusus|tema|karakter',
                'confidence_threshold' => 0.5,
                'action' => 'reply',
                'response_template' => 'Kami siap membuat buket custom sesuai keinginan Anda! Ceritakan desain apa yang Anda inginkan.',
                'context_slug' => 'product_selection',
                'next_context' => 'custom_design_brief',
                'priority' => 2,
                'is_active' => true,
            ],
            [
                'intent' => 'inquiry_payment',
                'pattern' => 'bayar|pembayaran|harga akhir|total|invoice|transfer',
                'confidence_threshold' => 0.55,
                'action' => 'escalate',
                'response_template' => 'Saya akan mengirimkan detail pembayaran untuk Anda. Silakan tunggu.',
                'context_slug' => 'order_confirmed',
                'next_context' => 'awaiting_payment',
                'priority' => 3,
                'is_active' => true,
            ],
            [
                'intent' => 'complaint',
                'pattern' => 'komplain|keluh|tidak puas|masalah|error|salah|rusak',
                'confidence_threshold' => 0.6,
                'action' => 'escalate',
                'response_template' => 'Maaf atas ketidaknyamanannya. Saya akan terhubungkan dengan tim kami untuk mengatasi masalah ini.',
                'context_slug' => null,
                'next_context' => 'handling_complaint',
                'priority' => 5, // Prioritas tinggi
                'is_active' => true,
            ],
            [
                'intent' => 'closing',
                'pattern' => 'terima kasih|thanks|bye|bye bye|dadah|sampai jumpa',
                'confidence_threshold' => 0.5,
                'action' => 'reply',
                'response_template' => 'Terima kasih sudah menghubungi kami! 😊 Sampai jumpa lagi!',
                'context_slug' => null,
                'next_context' => null,
                'priority' => 1,
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
