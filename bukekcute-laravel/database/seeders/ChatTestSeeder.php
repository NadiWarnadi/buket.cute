<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Message;
use App\Models\Order;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ChatTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Disable foreign key checks temporarily
        \DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // Clear old test data (child tables first)
        Message::truncate();
        Order::truncate();
        Customer::truncate();

        // Re-enable foreign key checks
        \DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // Create test customer dengan nomor WhatsApp format benar
        $customer = Customer::create([
            'name' => 'Budi Santoso',
            'phone' => '+6285123456789', // Format WhatsApp yang benar: +628xxx
            'address' => 'Jalan Sudirman No. 123, Jakarta Selatan',
        ]);

        echo "✅ Customer created: {$customer->name} ({$customer->phone})\n";

        // Create sample incoming messages for testing chatbot
        $messages = [
            [
                'customer_id' => $customer->id,
                'message_id' => 'msg_' . uniqid() . '_1',
                'from' => '6285123456789@c.us',
                'to' => 'bot@whatsapp',
                'body' => 'Halo, ada buket romantis gak?',
                'type' => 'text',
                'is_incoming' => true,
                'status' => 'delivered',
                'parsed' => false,
            ],
            [
                'customer_id' => $customer->id,
                'message_id' => 'msg_' . uniqid() . '_2',
                'from' => '6285123456789@c.us',
                'to' => 'bot@whatsapp',
                'body' => 'Berapa harga nya? Ada diskon?',
                'type' => 'text',
                'is_incoming' => true,
                'status' => 'read',
                'parsed' => false,
            ],
        ];

        foreach ($messages as $msg) {
            Message::create($msg);
        }

        echo "✅ Test messages created: " . count($messages) . " messages\n";

        // Create sample order
        $order = Order::create([
            'customer_id' => $customer->id,
            'total_price' => 300000,
            'status' => 'pending',
            'notes' => 'Order test - 2x Buket Romantis untuk Jalan Sudirman',
        ]);

        echo "✅ Test order created: Order #{$order->id} - Rp " . number_format($order->total_price, 0, ',', '.') . "\n";

        echo "\n🎉 Seeder completed!\n";
        echo "📱 Customer WhatsApp: {$customer->phone}\n";
        echo "➡️  Use this number to test in Node.js (scan QR dengan nomor ini)\n";
    }
}
