<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setting;

class WhatsappSettingsSeeder extends Seeder
{
    public function run()
    {
        // Format: [key => [value_dari_env, encrypt?]]
        $settings = [
            'store_whatsapp'     => [env('STORE_WHATSAPP', '62xxxxxx'), true],
            'service_url'        => [env('WHATSAPP_SERVICE_URL', 'http://localhost:3000'), false],
            'api_key'            => [env('WHATSAPP_API_KEY', 'your-super-secret-key'), true],
            'webhook_key'        => [env('WHATSAPP_WEBHOOK_KEY', 'your-super-secret-key'), true],
            'business_phone'     => [env('WHATSAPP_BUSINESS_PHONE', '+62xxxxxx'), true],
        ];

        foreach ($settings as $key => [$value, $encrypt]) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $encrypt ? encrypt($value) : $value]
            );
        }
    }
}