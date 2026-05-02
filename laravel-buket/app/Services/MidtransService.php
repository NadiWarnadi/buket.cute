<?php

namespace App\Services;

use App\Models\Order;
use Midtrans\Config;
use Midtrans\CoreApi;
use Illuminate\Support\Facades\Log;

class MidtransService
{
    public function __construct()
    {
        Config::$serverKey = config('midtrans.server_key');
        Config::$isProduction = config('midtrans.is_production');
        Config::$isSanitized = config('midtrans.is_sanitized');
        Config::$is3ds = config('midtrans.is_3ds');
    }

    /**
     * Buat transaksi QRIS (via CoreApi) untuk order.
     */
   public function createQrisTransaction(Order $order): array
{
    $customer = $order->customer;

    $transaction = [
        'payment_type' => 'gopay',
        'transaction_details' => [
            'order_id' => 'ORDER-' . $order->id . '-' . time(),
            'gross_amount' => (int) $order->total_price,
        ],
        'customer_details' => [
            'first_name' => $customer->name ?? 'Pelanggan',
            'phone' => $customer->phone,
            'address' => $customer->address,
        ],
        'gopay' => [
            'enable_callback' => true,
            'callback_url' => route('midtrans.webhook'),
        ],
    ];

    try {
        $responseObj = CoreApi::charge($transaction);
        $response = json_decode(json_encode($responseObj), true);
        Log::info('Midtrans response', ['response' => $response]);

        // Ambil URL QR code (generate-qr-code)
        $qrUrl = null;
        if (isset($response['actions'])) {
            foreach ($response['actions'] as $action) {
                if (strpos($action['name'], 'generate-qr-code') !== false) {
                    $qrUrl = $action['url'];
                    break;
                }
            }
        }

        if (!$qrUrl) {
            throw new \Exception('QR code URL tidak ditemukan di respons Midtrans.');
        }

        $order->update([
            'midtrans_order_id' => $response['order_id'],
            'midtrans_transaction_status' => $response['transaction_status'],
            'midtrans_transaction_id' => $response['transaction_id'],
            'midtrans_payment_type' => $response['payment_type'],
            'midtrans_qr_code_url' => $qrUrl,
            'midtrans_raw_response' => json_encode($response),
        ]);

        return [
            'success' => true,
            'qr_url' => $qrUrl,
            'order_id' => $response['order_id'],
             'expiry_time' => $response['expiry_time'] ?? null,
        ];
    } catch (\Exception $e) {
        Log::error('Midtrans charge error: ' . $e->getMessage());
        throw $e;
    }
}

    /**
     * Handle webhook notifikasi dari Midtrans.
     */
    public function handleNotification(array $payload): void
    {
        $orderId = $payload['order_id'];
        $order = Order::where('midtrans_order_id', $orderId)->first();
        if (!$order) {
            Log::warning("Midtrans notification for unknown order: $orderId");
            return;
        }

        $transactionStatus = $payload['transaction_status'];
        $order->update([
            'midtrans_transaction_status' => $transactionStatus,
            'midtrans_raw_response' => json_encode($payload),
        ]);

        if (in_array($transactionStatus, ['settlement', 'capture'])) {
            $order->update([
                'payment_status' => 'paid',
                'status' => 'processed',
            ]);
            app(WhatsAppService::class)->sendText(
                $order->customer->phone,
                "✅ Pembayaran pesanan #{$order->id} berhasil! Pesanan akan segera diproses."
            );
        } elseif ($transactionStatus === 'pending') {
            // masih menunggu
        } elseif (in_array($transactionStatus, ['deny', 'expire', 'cancel'])) {
            $order->update([
                'payment_status' => 'failed',
                'status' => 'cancelled',
            ]);
            app(WhatsAppService::class)->sendText(
                $order->customer->phone,
                "❌ Pembayaran pesanan #{$order->id} gagal atau dibatalkan."
            );
        }
    }
}