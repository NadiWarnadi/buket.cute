<?php

namespace App\Services;

use Midtrans\Config;
use Midtrans\CoreApi;
use App\Models\Order;
use Illuminate\Support\Facades\Log;

class MidtransService
{
    public function __construct()
    {
        Config::$serverKey = config('services.midtrans.server_key');
        Config::$clientKey = config('services.midtrans.client_key');
        Config::$isProduction = config('services.midtrans.is_production');
        Config::$isSanitized = true;
        Config::$is3ds = true;
    }

    /**
     * Buat transaksi bank transfer via Midtrans.
     * @param Order $order
     * @param string $bank 'bca', 'bni', 'mandiri'
     * @return array
     */
    public function createBankTransfer(Order $order, string $bank = 'bca'): array
    {
        // Susun item details dari order items
        $items = [];
        foreach ($order->items as $item) {
            $items[] = [
                'id' => $item->product_id,
                'price' => (int) $item->price,
                'quantity' => $item->quantity,
                'name' => $item->product->name ?? 'Produk',
            ];
        }

        // Unik order id (Midtrans tidak terima karakter selain huruf, angka, -_)
        $orderId = 'ORDER-' . $order->id . '-' . time();

        $params = [
            'payment_type' => 'bank_transfer',
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => (int) $order->total_price,
            ],
            'bank_transfer' => [
                'bank' => $bank,
                // Untuk BCA bisa tambahkan 'va_number' => '...' jika perlu
            ],
            'item_details' => $items,
            'customer_details' => [
                'first_name' => $order->customer->name ?? 'Pelanggan',
                'phone' => $order->customer->phone,
            ],
        ];

        try {
            $response = CoreApi::charge($params);
            Log::info('Midtrans bank transfer created', ['response' => $response]);

            return [
                'success' => true,
                'va_numbers' => $response->va_numbers ?? [],
                'transaction_id' => $response->transaction_id,
                'order_id' => $orderId,
                'raw' => $response,
            ];
        } catch (\Exception $e) {
            Log::error('Midtrans charge failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Buat transaksi QRIS (pakai Gopay/QRIS).
     * Bisa juga pakai payment_type 'qris' jika sudah didukung akun Midtrans.
     * Untuk sekarang kita pakai 'gopay' dulu sebagai contoh QR code.
     */
    public function createQRISPayment(Order $order): array
    {
        $items = [];
        foreach ($order->items as $item) {
            $items[] = [
                'id' => $item->product_id,
                'price' => (int) $item->price,
                'quantity' => $item->quantity,
                'name' => $item->product->name ?? 'Produk',
            ];
        }

        $orderId = 'ORDER-' . $order->id . '-' . time();

        $params = [
            'payment_type' => 'gopay', // atau 'qris' jika sudah enable
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => (int) $order->total_price,
            ],
            'item_details' => $items,
            'customer_details' => [
                'first_name' => $order->customer->name ?? 'Pelanggan',
                'phone' => $order->customer->phone,
            ],
        ];

        try {
            $response = CoreApi::charge($params);
            // Dari response gopay biasanya ada 'actions' untuk QR URL
            $qrUrl = null;
            if (isset($response->actions)) {
                foreach ($response->actions as $action) {
                    if ($action->name === 'generate-qr-code') {
                        $qrUrl = $action->url;
                        break;
                    }
                }
            }

            Log::info('Midtrans QRIS created', ['response' => $response]);

            return [
                'success' => true,
                'qr_code_url' => $qrUrl ?? ($response->qr_code_url ?? null),
                'transaction_id' => $response->transaction_id,
                'order_id' => $orderId,
                'raw' => $response,
            ];
        } catch (\Exception $e) {
            Log::error('Midtrans QRIS failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}