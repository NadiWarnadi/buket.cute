<?php

namespace App\Services;

use App\Models\Message;
use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PaymentManager
{
    /**
     * Metode pembayaran yang didukung
     */
    const METHODS = [
        'cod' => 'Cash on Delivery',
        'bank_transfer' => 'Transfer Bank',
        'midtrans' => 'Midtrans (Coming Soon)',
    ];

    /**
     * Status pembayaran
     */
    const STATUSES = [
        'pending' => 'Menunggu Pembayaran',
        'paid' => 'Sudah Dibayar',
        'failed' => 'Pembayaran Gagal',
        'refunded' => 'Dikembalikan',
    ];

    /**
     * Deteksi apakah pesan mengandung bukti transfer
     * Cek jika tipe pesan adalah image
     */
    public function detectPaymentProof(Message $message): bool
    {
        // Cek tipe pesan
        if ($message->type !== 'image') {
            return false;
        }

        // Cek apakah ada media path
        if (empty($message->media_path)) {
            return false;
        }

        // Cek apakah file masih ada
        if (!Storage::exists($message->media_path)) {
            Log::warning('Payment proof file not found', [
                'message_id' => $message->id,
                'media_path' => $message->media_path,
            ]);
            return false;
        }

        return true;
    }

    /**
     * Proses bukti pembayaran dari pesan
     */
    public function processPaymentProof(Message $message, Order $order): array
    {
        try {
            // Validasi bahwa ini bukti transfer
            if (!$this->detectPaymentProof($message)) {
                return [
                    'success' => false,
                    'error' => 'Pesan bukan berupa bukti transfer yang valid',
                ];
            }

            // Update order dengan bukti pembayaran
            $order->update([
                'payment_method' => 'bank_transfer',
                'payment_status' => 'paid',
                'payment_proof' => $message->media_path,
                'payment_data' => array_merge($order->payment_data ?? [], [
                    'proof_message_id' => $message->id,
                    'proof_received_at' => now(),
                    'proof_verified_by' => 'system', // bisa diubah ke admin id
                ]),
            ]);

            Log::info('Payment proof processed successfully', [
                'order_id' => $order->id,
                'message_id' => $message->id,
                'proof_path' => $message->media_path,
            ]);

            return [
                'success' => true,
                'message' => 'Bukti pembayaran berhasil diproses',
                'order' => $order,
            ];

        } catch (\Exception $e) {
            Log::error('Failed to process payment proof', [
                'order_id' => $order->id,
                'message_id' => $message->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Gagal memproses bukti pembayaran: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Verifikasi status pembayaran order
     */
    public function verifyPaymentStatus(Order $order): array
    {
        $status = [
            'payment_method' => $order->payment_method,
            'payment_status' => $order->payment_status,
            'has_proof' => !empty($order->payment_proof),
            'proof_valid' => false,
        ];

        // Cek validitas bukti jika ada
        if ($status['has_proof']) {
            $status['proof_valid'] = Storage::exists($order->payment_proof);
        }

        // Logika verifikasi berdasarkan metode
        switch ($order->payment_method) {
            case 'cod':
                // COD selalu dianggap paid saat delivery
                $status['is_verified'] = $order->status === 'completed';
                break;

            case 'bank_transfer':
                // Bank transfer perlu bukti yang valid
                $status['is_verified'] = $status['has_proof'] && $status['proof_valid'] && $order->payment_status === 'paid';
                break;

            case 'midtrans':
                // Midtrans akan menggunakan webhook/callback
                $status['is_verified'] = $order->payment_status === 'paid';
                break;

            default:
                $status['is_verified'] = false;
        }

        return $status;
    }

    /**
     * Set metode pembayaran untuk order
     */
    public function setPaymentMethod(Order $order, string $method): array
    {
        if (!array_key_exists($method, self::METHODS)) {
            return [
                'success' => false,
                'error' => 'Metode pembayaran tidak valid',
            ];
        }

        try {
            $order->update([
                'payment_method' => $method,
                'payment_status' => $method === 'cod' ? 'paid' : 'pending', // COD langsung paid
            ]);

            Log::info('Payment method updated', [
                'order_id' => $order->id,
                'method' => $method,
            ]);

            return [
                'success' => true,
                'message' => 'Metode pembayaran berhasil diubah',
                'order' => $order,
            ];

        } catch (\Exception $e) {
            Log::error('Failed to set payment method', [
                'order_id' => $order->id,
                'method' => $method,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Gagal mengubah metode pembayaran: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get informasi pembayaran untuk order
     */
    public function getPaymentInfo(Order $order): array
    {
        $verification = $this->verifyPaymentStatus($order);

        return [
            'order_id' => $order->id,
            'payment_method' => [
                'code' => $order->payment_method,
                'name' => self::METHODS[$order->payment_method] ?? 'Unknown',
            ],
            'payment_status' => [
                'code' => $order->payment_status,
                'name' => self::STATUSES[$order->payment_status] ?? 'Unknown',
            ],
            'has_proof' => $verification['has_proof'],
            'proof_valid' => $verification['proof_valid'],
            'is_verified' => $verification['is_verified'],
            'proof_url' => $order->payment_proof ? Storage::url($order->payment_proof) : null,
            'payment_data' => $order->payment_data,
        ];
    }

    /**
     * Get pesan konfirmasi pembayaran berdasarkan metode
     */
    public function getPaymentConfirmationMessage(Order $order): string
    {
        $method = $order->payment_method;
        $total = number_format($order->total_price, 0, ',', '.');

        switch ($method) {
            case 'cod':
                return "💰 *Pembayaran COD*\n\n" .
                       "Total pembayaran: Rp {$total}\n" .
                       "Metode: Bayar di tempat\n" .
                       "Status: Akan dibayar saat pengiriman\n\n" .
                       "Terima kasih telah memesan! 🎉";

            case 'bank_transfer':
                return "💳 *Pembayaran Transfer Bank*\n\n" .
                       "Total pembayaran: Rp {$total}\n" .
                       "Metode: Transfer ke rekening\n\n" .
                       "Silakan transfer ke:\n" .
                       "🏦 BCA: 1234567890\n" .
                       "🏦 Mandiri: 0987654321\n" .
                       "🏦 BNI: 1122334455\n\n" .
                       "Kirim bukti transfer ke chat ini.\n" .
                       "Status akan otomatis terupdate setelah verifikasi.";

            case 'midtrans':
                return "💳 *Pembayaran Midtrans*\n\n" .
                       "Total pembayaran: Rp {$total}\n" .
                       "Metode: Midtrans (Coming Soon)\n\n" .
                       "Fitur ini sedang dalam pengembangan.";

            default:
                return "💰 *Konfirmasi Pembayaran*\n\n" .
                       "Total: Rp {$total}\n" .
                       "Metode pembayaran akan dikonfirmasi oleh admin.";
        }
    }

    /**
     * Get daftar metode pembayaran yang tersedia
     */
    public function getAvailableMethods(): array
    {
        return self::METHODS;
    }

    /**
     * Get daftar status pembayaran
     */
    public function getPaymentStatuses(): array
    {
        return self::STATUSES;
    }
}