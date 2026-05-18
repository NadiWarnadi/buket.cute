<?php

namespace App\Observers;

use App\Models\Order;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Log;

class OrderObserver
{
    protected WhatsAppService $wa;

    public function __construct(WhatsAppService $wa)
    {
        $this->wa = $wa;
    }

    /**
     * Handle the Order "updated" event.
     */
    public function updated(Order $order): void
    {   
        Log::info('OrderObserver updated triggered', ['order_id' => $order->id]);
        // Notifikasi perubahan status pesanan
        if ($order->wasChanged('status')) {
            $oldStatus = $order->getOriginal('status');
            $newStatus = $order->status;

            if ($oldStatus !== $newStatus) {
                $this->sendStatusNotification($order, $oldStatus, $newStatus);
            }
        }

        // Notifikasi perubahan status pembayaran
        if ($order->wasChanged('payment_status')) {
            $oldPaymentStatus = $order->getOriginal('payment_status');
            $newPaymentStatus = $order->payment_status;

            if ($oldPaymentStatus !== $newPaymentStatus) {
                $this->sendPaymentStatusNotification($order, $oldPaymentStatus, $newPaymentStatus);
            }
        }
    }

    /**
     * Kirim WhatsApp notifikasi perubahan status pesanan.
     */
    protected function sendStatusNotification(Order $order, string $oldStatus, string $newStatus): void
    {
        $customer = $order->customer;
        if (!$customer || !$customer->phone) {
            Log::warning('Order status change but no customer phone', ['order_id' => $order->id]);
            return;
        }

        $message = $this->buildStatusMessage($order, $oldStatus, $newStatus);
        if (!$message) {
            return;
        }

        try {
            $result = $this->wa->sendText($customer->phone, $message);
            Log::info('Order status notification sent', [
                'order_id' => $order->id,
                'phone' => $customer->phone,
                'new_status' => $newStatus,
                'sent' => $result['success'] ?? false
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send order status notification', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Kirim WhatsApp notifikasi perubahan status pembayaran.
     */
    protected function sendPaymentStatusNotification(Order $order, string $oldStatus, string $newStatus): void
    {
        $customer = $order->customer;
        if (!$customer || !$customer->phone) {
            Log::warning('Payment status change but no customer phone', ['order_id' => $order->id]);
            return;
        }

        $message = $this->buildPaymentStatusMessage($order, $oldStatus, $newStatus);
        if (!$message) {
            return;
        }

        try {
            $result = $this->wa->sendText($customer->phone, $message);
            Log::info('Payment status notification sent', [
                'order_id' => $order->id,
                'phone' => $customer->phone,
                'new_payment_status' => $newStatus,
                'payment_method' => $order->payment_method,
                'sent' => $result['success'] ?? false
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send payment status notification', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Buat teks notifikasi berdasarkan perubahan status pesanan.
     */
    protected function buildStatusMessage(Order $order, string $oldStatus, string $newStatus): ?string
    {
        $statusLabels = [
            'pending'    => '⏳ Menunggu konfirmasi admin',
            'processing' => '🔄 Sedang diproses',
            'shipped'    => '🚚 Dalam pengiriman',
            'delivered'  => '✅ Sudah sampai tujuan',
            'cancelled'  => '❌ Dibatalkan',
        ];

        $newLabel = $statusLabels[$newStatus] ?? $newStatus;

        // Hanya kirim notifikasi untuk status yang penting
        if (!in_array($newStatus, ['processing', 'shipped', 'delivered', 'cancelled'])) {
            return null;
        }

        $message = "📦 *Update Pesanan #{$order->id}*\n\n"
                 . "Status: {$newLabel}\n\n";

        if ($newStatus === 'processing') {
            $message .= "Pesanan Kakak sedang kami proses. Terima kasih sudah sabar menunggu 🙏";
        } elseif ($newStatus === 'shipped') {
            $message .= "Pesanan telah dikirim! Kurir akan segera tiba. Terima kasih sudah berbelanja di Buket Cute 🌸";
        } elseif ($newStatus === 'delivered') {
            $message .= "Pesanan sudah sampai di tujuan. Semoga Kakak suka dengan produk kami! Jangan lupa order lagi ya 😊";
        } elseif ($newStatus === 'cancelled') {
            $message .= "Pesanan Kakak dibatalkan. Jika ada pertanyaan, silakan hubungi admin. Kami tunggu order berikutnya.";
        }

        return $message;
    }

    /**
     * Buat teks notifikasi perubahan status pembayaran.
     * Sekarang MENYERTAKAN metode pembayaran (COD, transfer, QRIS).
     */
protected function buildPaymentStatusMessage(Order $order, string $oldStatus, string $newStatus): ?string
{
    $methodLabels = [
        'cod'      => 'COD (Bayar di Tempat)',
        'transfer' => 'Transfer Bank',
        'qris'     => 'QRIS',
    ];
    $paymentMethod = $order->payment_method ?? 'tidak diketahui';
    $methodLabel = $methodLabels[$paymentMethod] ?? $paymentMethod;

    $statusLabels = [
        'pending' => '⏳ Menunggu pembayaran',
        'paid'    => '✅ Pembayaran dikonfirmasi',
        'failed'  => '❌ Pembayaran gagal',
        'expired' => '⌛ Pembayaran kedaluwarsa',
    ];
    $statusLabel = $statusLabels[$newStatus] ?? $newStatus;

    // Khusus COD: hanya kirim notifikasi saat status menjadi 'paid'
    if ($paymentMethod === 'cod') {
        if ($newStatus !== 'paid') {
            return null; // Abaikan status lain untuk COD
        }
        $message = "💰 *Update Pembayaran Pesanan #{$order->id}*\n"
                 . "Metode: {$methodLabel}\n"
                 . "Status: {$statusLabel}\n\n"
                 . "Pembayaran COD telah dilunaskan saat barang diterima. Terima kasih ya Kakak! 😊";
        return $message;
    }

    // Untuk transfer/QRIS: hanya kirim jika status penting
    if (!in_array($newStatus, ['paid', 'failed', 'expired'])) {
        return null;
    }

    $message = "💰 *Update Pembayaran Pesanan #{$order->id}*\n"
             . "Metode: {$methodLabel}\n"
             . "Status: {$statusLabel}\n\n";

    if ($newStatus === 'paid') {
        $message .= "Pembayaran {$methodLabel} sudah kami terima. Pesanan akan segera diproses. Terima kasih! 🙏";
    } elseif ($newStatus === 'failed') {
        $message .= "Pembayaran {$methodLabel} Kakak gagal diproses. Silakan coba lagi atau gunakan metode pembayaran lain. Jika butuh bantuan, hubungi admin kami.";
    } elseif ($newStatus === 'expired') {
        $message .= "Batas waktu pembayaran {$methodLabel} sudah habis. Silakan buat pesanan baru atau hubungi admin untuk perpanjangan.";
    }

    return $message;
}
}