<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;

class SettingsController extends Controller
{
    /**
     * Ambil QR Code dari server Node.js
     * Endpoint: GET /api/settings/whatsapp/qr-code
     */
    public function getWhatsAppQr(): JsonResponse
    {
        $apiUrl = Setting::getValue('whatsapp_api_url', 'http://localhost:3000');
        $apiKey = Setting::getValue('whatsapp_api_key');

        if (empty($apiKey)) {
            return response()->json([
                'success' => false,
                'message' => 'API Key WhatsApp belum dikonfigurasi'
            ], 400);
        }

        try {
            $response = Http::withHeaders(['X-API-Key' => $apiKey])
                ->timeout(10)
                ->get($apiUrl . '/api/qr-code');

            if ($response->successful()) {
                $data = $response->json();
                // Simpan QR code ke database untuk fallback
                if (!empty($data['qrCode'])) {
                    Setting::setValue('wa_qr_code', $data['qrCode']);
                }

                return response()->json([
                    'success' => true,
                    'qr_code' => $data['qrCode'] ?? null,
                    'message' => 'OK'
                ]);
            }

            // Jika response tidak sukses, lempar exception
            throw new \Exception('Node.js response error: ' . $response->body());

        } catch (\Exception $e) {
            // Fallback: gunakan QR code yang tersimpan di database
            $cachedQr = Setting::getValue('wa_qr_code');
            if (!empty($cachedQr)) {
                return response()->json([
                    'success' => true,
                    'qr_code' => $cachedQr,
                    'cached'  => true,
                    'message' => 'QR Code diambil dari cache (server Node.js tidak merespon)'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'QR Code tidak tersedia. Pastikan server WhatsApp Node.js berjalan dan API Key benar.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}