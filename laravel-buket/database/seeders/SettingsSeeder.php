<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setting;
use Illuminate\Support\Facades\Config;

class SettingsSeeder extends Seeder
{
    public function run()
    {
        // Baca dari .env, dengan fallback value default
        $defaults = [
            // General
             ['key' => 'whatsapp_enabled', 'value' => env('WHATSAPP_ENABLED', '0'), 'type' => 'boolean', 'label' => 'Aktifkan WhatsApp Bot', 'category' => 'whatsapp'],
    ['key' => 'whatsapp_phone', 'value' => env('WHATSAPP_PHONE', ''), 'type' => 'string', 'label' => 'Nomor WhatsApp Toko', 'category' => 'whatsapp'],
    ['key' => 'whatsapp_api_url', 'value' => env('WHATSAPP_API_URL', 'http://localhost:3000'), 'type' => 'string', 'label' => 'URL API WhatsApp', 'category' => 'whatsapp'],
    ['key' => 'whatsapp_api_key', 'value' => env('WHATSAPP_API_KEY', ''), 'type' => 'string', 'label' => 'API Key WhatsApp', 'category' => 'whatsapp'],
    ['key' => 'wa_connection_status', 'value' => 'disconnected', 'type' => 'string', 'label' => 'Status Koneksi', 'category' => 'whatsapp'],
    ['key' => 'wa_qr_code', 'value' => null, 'type' => 'text', 'label' => 'QR Code', 'category' => 'whatsapp'],
    ['key' => 'whatsapp_webhook_key', 'value' => env('WHATSAPP_WEBHOOK_KEY', bin2hex(random_bytes(32))), 'type' => 'string', 'label' => 'Webhook Key', 'category' => 'whatsapp'],
];
        foreach ($defaults as $default) {
            Setting::updateOrCreate(
                ['key' => $default['key']],
                $default
            );
        }
    }
}