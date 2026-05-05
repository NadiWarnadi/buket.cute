<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SettingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class WhatsappStatusController extends Controller
{
    public function update(Request $request)
    {
        // 1. Verifikasi API key
        $apiKey = SettingService::getDecrypted('api_key');
        if ($request->header('x-api-key') !== $apiKey) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // 2. Ambil data & Berikan nilai default jika ada yang kosong
        $data = [
            'connected'  => (bool) $request->input('connected', false),
            'status'     => $request->input('status', 'disconnected'),
            'user'       => $request->input('user'),
            'message'    => $request->input('message'),
            'updated_at' => now()->toDateTimeString(), // Penting untuk tracking waktu
        ];

        // 3. Simpan di cache selamanya
        Cache::forever('whatsapp_connection_status', $data);

        return response()->json([
            'success' => true,
            'data_saved' => $data // Beri feedback data apa yang berhasil disimpan
        ]);
    }
}