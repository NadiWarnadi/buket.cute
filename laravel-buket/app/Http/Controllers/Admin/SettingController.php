<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SettingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class SettingController extends Controller
{   
    public function index()
{
     $settings = [
        'store_whatsapp' => SettingService::getDecrypted('store_whatsapp'),
        'service_url' => SettingService::get('service_url'),
        'api_key' => SettingService::getDecrypted('api_key'),
        'webhook_key' => SettingService::getDecrypted('webhook_key'),
        'business_phone' => SettingService::getDecrypted('business_phone'),
    ];
    return view('admin.settings.index'); // bisa halaman utama pengaturan nanti
}

    public function editWhatsApp()
    {
        $settings = [
            'store_whatsapp'   => SettingService::getDecrypted('store_whatsapp'),
            'service_url'      => SettingService::get('service_url'),
            'api_key'          => SettingService::getDecrypted('api_key'),
            'webhook_key'      => SettingService::getDecrypted('webhook_key'),
            'business_phone'   => SettingService::getDecrypted('business_phone'),
        ];

        return view('admin.settings.whatsapp', compact('settings'));
    }

    public function updateWhatsApp(Request $request)
    {
        $validated = $request->validate([
            'store_whatsapp'   => 'required|string',
            'service_url'      => 'required|url',
            'api_key'          => 'required|string',
            'webhook_key'      => 'required|string',
            'business_phone'   => 'required|string',
        ]);

        
        SettingService::set('store_whatsapp', $validated['store_whatsapp'], true);
        SettingService::set('service_url', $validated['service_url'], false);
        SettingService::set('api_key', $validated['api_key'], true);
        SettingService::set('webhook_key', $validated['webhook_key'], true);
        SettingService::set('business_phone', $validated['business_phone'], true);

        return redirect()->route('admin.settings.whatsapp')
            ->with('success', 'Pengaturan WhatsApp berhasil diperbarui.');
    }
    public function getQrCode()
{
    $serviceUrl = SettingService::get('service_url');
    $apiKey = SettingService::getDecrypted('api_key');

    try {
        $response = Http::withHeaders([
            'x-api-key' => $apiKey,
        ])->get("{$serviceUrl}/api/qr-code");

        if ($response->successful()) {
            return response()->json($response->json());
        }

        return response()->json([
            'success' => false,
            'message' => 'Tidak dapat mengambil QR Code',
        ], 500);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Gagal terhubung ke wa-service: ' . $e->getMessage(),
        ], 500);
    }
}

public function getStatus()
    {
        // 1. Coba ambil dari Cache (yang diupdate oleh Webhook)
        $cachedStatus = Cache::get('whatsapp_connection_status');

        if ($cachedStatus) {
            return response()->json([
                'connected' => $cachedStatus['connected'] ?? false,
                'user'      => $cachedStatus['user'] ?? null,
                'message'   => $cachedStatus['message'] ?? 'Status dari cache',
                'source'    => 'webhook' // Penanda data dari cache
            ]);
        }

        // 2. Jika cache kosong (misal baru reset), lakukan fallback tembak API manual
        $serviceUrl = SettingService::get('service_url');
        $apiKey = SettingService::getDecrypted('api_key');

        if (empty($serviceUrl) || empty($apiKey)) {
            return response()->json(['connected' => false, 'message' => 'Setting tidak lengkap'], 400);
        }

        try {
            $response = Http::withHeaders(['x-api-key' => $apiKey])
                ->timeout(5)
                ->get(rtrim($serviceUrl, '/') . '/api/status');

            if ($response->successful()) {
                $data = $response->json();
                $statusData = [
                    'connected' => $data['status']['connected'] ?? false,
                    'user'      => $data['status']['user'] ?? null,
                    'message'   => $data['status']['message'] ?? '',
                ];
                
                // Simpan ke cache agar request berikutnya tidak tembak API lagi
                Cache::forever('whatsapp_connection_status', $statusData);

                return response()->json($statusData);
            }
        } catch (\Exception $e) {
            return response()->json(['connected' => false, 'message' => 'Gagal cek status manual'], 500);
        }

        return response()->json(['connected' => false, 'message' => 'Disconnected']);
    }

}