<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Services\MidtransService;
use App\Http\Controllers\Controller; 
use Illuminate\Support\Facades\Log;

class MidtransController extends Controller
{
    public function webhook(Request $request, MidtransService $midtransService)
    {
        $payload = $request->all();
        Log::info('Midtrans webhook received', $payload);

        // Verifikasi signature key (wajib)
        $serverKey = config('midtrans.server_key');
        $orderId = $payload['order_id'] ?? '';
        $statusCode = $payload['status_code'] ?? '';
        $grossAmount = $payload['gross_amount'] ?? '';
        $signatureKey = $payload['signature_key'] ?? '';

        $computedHash = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);

        if ($computedHash !== $signatureKey) {
            Log::warning('Midtrans webhook signature mismatch', [
                'computed' => $computedHash,
                'received' => $signatureKey,
            ]);
            abort(403, 'Invalid signature');
        }

        $midtransService->handleNotification($payload);

        return response()->json(['status' => 'ok'], 200);
    }
}