<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;

class SettingController extends Controller
{
    /**
     * Tampilkan halaman pengaturan utama
     */
    public function index()
    {
        $settings = [
            'general'     => Setting::getByCategory('general'),
            'stock'       => Setting::getByCategory('stock'),
            'notification' => Setting::getByCategory('notification'),
            'whatsapp'    => Setting::getByCategory('whatsapp'),
        ];

        return view('admin.settings.index', compact('settings'));
    }

    /**
     * Update pengaturan dari form
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            // General
            'app_name'          => 'required|string|max:255',
            'app_description'   => 'nullable|string',
            'app_timezone'      => 'required|string',
            'app_currency'      => 'required|string|max:10',
            'order_prefix'      => 'required|string|max:10',
            // Stock
            'low_stock_threshold' => 'required|integer|min:1',
            'auto_order_timeout'  => 'required|integer|min:1',
            // Notification
            'notification_email'  => 'nullable|email',
            // WhatsApp
            'whatsapp_phone'      => 'nullable|string',
            'whatsapp_api_url'    => 'nullable|url',
            'whatsapp_api_key'    => 'nullable|string', // kosong = tidak diubah
        ]);

        // Boolean checkboxes
        $validated['notification_enabled'] = $request->has('notification_enabled') ? '1' : '0';
        $validated['whatsapp_enabled']     = $request->has('whatsapp_enabled') ? '1' : '0';

        // Simpan setiap key, kecuali whatsapp_api_key jika kosong
        foreach ($validated as $key => $value) {
            if ($key === 'whatsapp_api_key' && empty($value)) {
                continue; // jangan update, biarkan nilai lama
            }
            Setting::setValue($key, (string) $value);
        }

        return redirect()->route('admin.settings.index')
            ->with('success', 'Pengaturan berhasil disimpan.');
    }

    /**
     * Halaman khusus manajemen QR Code WhatsApp
     */
    public function whatsappQr()
    {
        return view('admin.settings.whatsapp-qr');
    }

    /**
     * Cek status koneksi WhatsApp dari Node.js
     * Endpoint: GET admin/settings/whatsapp-status (AJAX)
     */
    public function checkWhatsAppStatus(): JsonResponse
    {
        $apiUrl = Setting::getValue('whatsapp_api_url', 'http://localhost:3000');
        $apiKey = Setting::getValue('whatsapp_api_key');

        $connected = false;
        $phone     = Setting::getValue('whatsapp_phone');

        if (empty($apiKey)) {
            return response()->json([
                'connected' => false,
                'status'    => 'disabled',
                'phone'     => $phone,
                'message'   => 'API Key WhatsApp belum dikonfigurasi'
            ]);
        }

        try {
            $response = Http::withHeaders(['X-API-Key' => $apiKey])
                ->timeout(5)
                ->get($apiUrl . '/api/status');

            if ($response->successful()) {
                $data = $response->json();
                $connected = $data['status']['connected'] ?? false;
                // Update status di database
                Setting::setValue('wa_connection_status', $connected ? 'connected' : 'disconnected');
                // Update nomor jika ada
                if (!empty($data['status']['user'])) {
                    Setting::setValue('whatsapp_phone', $data['status']['user']);
                    $phone = $data['status']['user'];
                }
            } else {
                // Node.js merespon error
                $connected = false;
            }
        } catch (\Exception $e) {
            // Node.js offline, gunakan status terakhir dari database
            $connected = Setting::getValue('wa_connection_status') === 'connected';
        }

        return response()->json([
            'connected' => $connected,
            'status'    => $connected ? 'connected' : 'disconnected',
            'phone'     => $phone,
        ]);
    }

    /**
     * Reset sesi WhatsApp di Node.js (menghasilkan QR baru)
     * Endpoint: POST admin/settings/rescan-qr
     */
    public function rescanQR(): JsonResponse
    {
        $apiUrl = Setting::getValue('whatsapp_api_url');
        $apiKey = Setting::getValue('whatsapp_api_key');

        if (empty($apiKey)) {
            return response()->json([
                'success' => false,
                'message' => 'API Key WhatsApp belum dikonfigurasi'
            ], 400);
        }

        try {
            $response = Http::withHeaders(['X-API-Key' => $apiKey])
                ->timeout(5)
                ->post($apiUrl . '/api/reset-session');

            $data = $response->json();

            // Hapus QR code yang lama dari database
            Setting::setValue('wa_qr_code', null);
            // Set status menjadi disconnected sementara
            Setting::setValue('wa_connection_status', 'disconnected');

            return response()->json([
                'success' => true,
                'message' => $data['message'] ?? 'Sesi WhatsApp direset, silakan scan QR code baru.'
            ]);
        } catch (\Exception $e) {
            // Jika Node.js tidak merespon, tetap hapus QR dari database
            Setting::setValue('wa_qr_code', null);
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghubungi server WhatsApp. Pastikan server Node.js berjalan.'
            ], 500);
        }
    }
}