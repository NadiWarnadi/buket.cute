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
        // Hanya kirim notifikasi jika status berubah
        if ($order->isDirty('status')) {
            $oldStatus = $order->getOriginal('status');
            $newStatus = $order->status;

            // Hindari mengirim notifikasi untuk status yang sama atau tidak relevan
            if ($oldStatus === $newStatus) {
                return;
            }

            // Kirim pesan ke customer
            $this->sendStatusNotification($order, $oldStatus, $newStatus);
        }

        // [Opsional] Juga kirim notifikasi jika status pembayaran berubah manual
        // (webhook Midtrans sudah kirim notif sendiri, jadi di sini bisa diabaikan)
    }

    /**
     * Kirim WhatsApp notifikasi perubahan status.
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
     * Buat teks notifikasi berdasarkan perubahan status.
     */
    protected function buildStatusMessage(Order $order, string $oldStatus, string $newStatus): ?string
    {
        // Label status untuk ditampilkan
        $statusLabels = [
            'pending'    => '⏳ Menunggu konfirmasi admin',
            'processing' => '🔄 Sedang diproses',
            'shipped'    => '🚚 Dalam pengiriman',
            'delivered'  => '✅ Sudah sampai tujuan',
            'cancelled'  => '❌ Dibatalkan',
        ];

        $newLabel = $statusLabels[$newStatus] ?? $newStatus;

        // Hanya kirim notifikasi untuk status yang benar‑benar penting
        // (bisa disesuaikan)
        if (!in_array($newStatus, ['processing', 'shipped', 'delivered', 'cancelled'])) {
            return null;
        }

        $message = "📦 *Update Pesanan #{$order->id}*\n\n"
                 . "Status: {$newLabel}\n\n";

        // Tambahkan keterangan khusus
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
}