<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\File;

class SettingController extends Controller
{
    /**
     * Settings file path
     */
    private const SETTINGS_FILE = 'app_settings.json';

    /**
     * Get all settings
     */
    private function getSettings()
    {
        $path = storage_path(self::SETTINGS_FILE);
        
        if (File::exists($path)) {
            return json_decode(File::get($path), true) ?? $this->getDefaultSettings();
        }
        
        return $this->getDefaultSettings();
    }

    /**
     * Get default settings
     */
    private function getDefaultSettings()
    {
        return [
            'app_name' => 'Toko Bucket Cutie',
            'app_description' => 'Sistem Informasi Pemesanan',
            'app_timezone' => 'Asia/Jakarta',
            'app_currency' => 'IDR',
            'whatsapp_enabled' => false,
            'whatsapp_phone' => '',
            'whatsapp_api_url' => '',
            'whatsapp_api_key' => '',
            'notification_email' => '',
            'notification_enabled' => false,
            'order_prefix' => 'ORD',
            'low_stock_threshold' => 5,
            'auto_order_timeout' => 24, // jam
        ];
    }

    /**
     * Save settings to file
     */
    private function saveSettings($settings)
    {
        $path = storage_path(self::SETTINGS_FILE);
        File::put($path, json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    /**
     * Display settings page
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
            'app_name' => 'required|string|max:255',
            'app_description' => 'nullable|string|max:1000',
            'app_timezone' => 'required|string',
            'app_currency' => 'required|string|max:10',
            'notification_email' => 'nullable|email',
            'notification_enabled' => 'boolean',
            'order_prefix' => 'required|string|max:10',
            'low_stock_threshold' => 'required|integer|min:1',
            'auto_order_timeout' => 'required|integer|min:1',
            'whatsapp_enabled' => 'boolean',
            'whatsapp_phone' => 'nullable|string',
            'whatsapp_api_url' => 'nullable|url',
            'whatsapp_api_key' => 'nullable|string',
        ]);

        // Handle checkbox yang tidak terkirim
        if (!$request->has('notification_enabled')) {
            $validated['notification_enabled'] = false;
        }
        if (!$request->has('whatsapp_enabled')) {
            $validated['whatsapp_enabled'] = false;
        }

        $this->saveSettings($validated);

        return redirect()->route('admin.settings.index')
                        ->with('success', 'Pengaturan berhasil disimpan.');
    }

    /**
     * Check WhatsApp connection status
     */
    public function checkWhatsAppStatus()
    {
        $settings = $this->getSettings();

        if (!$settings['whatsapp_enabled'] || !$settings['whatsapp_api_url']) {
            return response()->json([
                'status' => 'disabled',
                'message' => 'WhatsApp belum diaktifkan',
            ]);
        }

        // Di sini bisa ditambahkan logic untuk check ke WhatsApp API
        // Untuk sekarang, akan return mock status

        return response()->json([
            'status' => 'connected',
            'message' => 'WhatsApp API terhubung',
            'phone' => $settings['whatsapp_phone'] ?? '-',
            'last_checked' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Rescan QR Code untuk WhatsApp
     */
    public function rescanQR()
    {
        // Logic untuk trigger QR code rescan bisa ditambahkan di sini
        // Ini akan memanggil WhatsApp API untuk generate QR code baru

        return response()->json([
            'status' => 'success',
            'message' => 'QR Code di-generate ulang',
            'redirect' => route('admin.settings.index'),
        ]);
    }
}
