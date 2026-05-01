<?php

namespace Database\Seeders;

use App\Models\MasterState;
use Illuminate\Database\Seeder;

class MasterStateSeeder extends Seeder
{
    public function run(): void
    {
        MasterState::truncate();

        $states = [
            [
                'id' => 1,
                'name' => 'greeting',
                'type' => 'greeting',
                'prompt_text' => 'Halo {name}! Selamat datang di Buket Cute. 🌸',
                'next_state_id' => 2, // fallback jika data belum lengkap
                'prerequisite_keys' => json_encode(['name', 'address']),
            ],
            [
                'id' => 2,
                'name' => 'collect_name_address',
                'type' => 'input',
                'input_key' => 'name_address',
                'prompt_text' => 'Boleh tau nama dan alamat lengkapnya dulu ya?',
                'next_state_id' => 3,
                'prerequisite_keys' => json_encode(['name', 'address']),
                'validation_rules' => json_encode(['min_name_length' => 3, 'min_address_length' => 10]),
            ],
            [
                'id' => 3,
                'name' => 'choose_product',
                'type' => 'input',
                'input_key' => 'product',
                'prompt_text' => 'Mau buket seperti apa? Bisa sebutkan nama atau ketik *katalog*.',
                'next_state_id' => 10,
                'prerequisite_keys' => json_encode(['product_name']),
            ],
            [
                'id' => 4,
                'name' => 'collect_quantity',
                'type' => 'input',
                'input_key' => 'quantity',
                'prompt_text' => 'Mau pesan berapa banyak?',
                'validation_rules' => json_encode(['numeric', 'min:1']),
                'next_state_id' => 5,
                'prerequisite_keys' => json_encode(['quantity']),
            ],
            [
                'id' => 5,
                'name' => 'collect_payment',
                'type' => 'input',
                'input_key' => 'payment',
                'prompt_text' => 'Pilih metode pembayaran: 1️⃣ COD, 2️⃣ Transfer, 3️⃣ QRIS',
                'validation_rules' => json_encode(['in:cod,transfer,qris,1,2,3']),
                'next_state_id' => 6,
                'prerequisite_keys' => json_encode(['payment_method']),
            ],
            [
                'id' => 6,
                'name' => 'confirm_order',
                'type' => 'decision',
                'prompt_text' => 'Ketik *ya* untuk lanjut atau *tidak* untuk ubah.',
                'next_state_id' => 7,
                'fallback_state_id' => 3,
                'prerequisite_keys' => json_encode(['name', 'address', 'product_name', 'quantity', 'payment_method']),
            ],
            [
                'id' => 7,
                'name' => 'order_created',
                'type' => 'output',
                'prompt_text' => 'Pesanan berhasil dibuat! 🎉',
            ],
            [
                'id' => 8,
                'name' => 'tracking',
                'type' => 'fuzzy_inquiry',
                'prompt_text' => '📦 Cek status pesanan...',
                'fuzzy_context' => 'tracking',
            ],
            [
                'id' => 9,
                'name' => 'complaint',
                'type' => 'fuzzy_inquiry',
                'prompt_text' => '🙏 Keluhan kamu sudah dicatat.',
                'fuzzy_context' => 'complaint',
            ],
            [
                'id' => 10,
                'name' => 'collect_card_message',
                'type' => 'input',
                'input_key' => 'card_message',
                'prompt_text' => 'Mau tulis kartu ucapan apa? (Opsional, ketik *skip* jika tidak perlu)',
                'next_state_id' => 4,
                'prerequisite_keys' => json_encode([]),
            ],
        ];

        foreach ($states as $state) {
            MasterState::create($state);
        }

        $this->command->info('✅ Master States (10) seeded!');
    }
}