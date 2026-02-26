<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;

class SettingController extends Controller
{
    /**
     * Show settings form
     */
    public function index()
    {
        $settings = $this->getSettings();
        return view('admin.settings.index', compact('settings'));
    }

    /**
     * Update settings
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'store_name' => 'required|string|max:100',
            'store_address' => 'required|string|max:255',
            'store_phone' => 'required|string|max:20',
            'store_whatsapp' => 'required|string|max:20',
            'store_email' => 'required|email',
            'store_description' => 'nullable|string',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'auto_reply_enabled' => 'boolean',
            'auto_read_messages' => 'boolean',
        ]);

        // Save logo if uploaded
        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('settings', 'public');
            $validated['logo'] = $path;
        }

        // Save settings to Laravel settings or env file
        foreach ($validated as $key => $value) {
            if ($key !== 'logo' || $request->hasFile('logo')) {
                $this->saveSetting($key, $value);
            }
        }

        return back()->with('success', 'Pengaturan toko berhasil diperbarui!');
    }

    /**
     * Get all settings
     */
    private function getSettings()
    {
        return [
            'store_name' => env('STORE_NAME', 'Buket Cute'),
            'store_address' => env('STORE_ADDRESS', ''),
            'store_phone' => env('STORE_PHONE', ''),
            'store_whatsapp' => env('STORE_WHATSAPP', ''),
            'store_email' => env('STORE_EMAIL', ''),
            'store_description' => env('STORE_DESCRIPTION', ''),
            'auto_reply_enabled' => env('AUTO_REPLY_ENABLED', true),
            'auto_read_messages' => env('AUTO_READ_MESSAGE', false),
            'logo' => env('STORE_LOGO', null),
        ];
    }

    /**
     * Save individual setting
     */
    private function saveSetting($key, $value)
    {
        $envFile = base_path('.env');
        $envContent = file_get_contents($envFile);

        $key = strtoupper($key);

        if (strpos($envContent, "{$key}=") !== false) {
            // Update existing
            $envContent = preg_replace(
                "/^{$key}=.*$/m",
                "{$key}=" . (is_bool($value) ? ($value ? 'true' : 'false') : "\"$value\""),
                $envContent
            );
        } else {
            // Add new
            $envContent .= "\n{$key}=" . (is_bool($value) ? ($value ? 'true' : 'false') : "\"$value\"");
        }

        file_put_contents($envFile, $envContent);
    }

    /**
     * Check WhatsApp connection status
     */
    public function checkWhatsAppStatus()
    {
        try {
            $response = \Http::timeout(5)->get('http://localhost:3000/api/status');

            if ($response->ok()) {
                $data = $response->json();
                return response()->json([
                    'connected' => $data['connected'] ?? false,
                    'bot_jid' => $data['bot_jid'] ?? null,
                    'message' => $data['connected'] ? '✅ Terhubung ke WhatsApp' : '❌ Tidak terhubung',
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'connected' => false,
                'message' => '❌ Node.js Bot tidak berjalan',
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Rescan WhatsApp QR Code
     */
    public function rescanQR()
    {
        try {
            // Delete auth_info folder di Node.js untuk force rescan
            $response = \Http::post('http://localhost:3000/api/rescan-qr');

            if ($response->ok()) {
                return back()->with('success', 'QR Code sudah di-reset. Silakan scan ulang di terminal Node.js');
            }
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal me-reset QR Code: ' . $e->getMessage());
        }
    }
}
