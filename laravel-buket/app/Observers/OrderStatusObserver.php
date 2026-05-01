<?php

namespace App\Observers;

use App\Models\Order;
use App\Services\WhatsAppService;
use App\Models\Message;
use Illuminate\Support\Facades\Log;

class OrderStatusObserver
{
    protected WhatsAppService $waService;

    public function __construct(WhatsAppService $waService)
    {
        $this->waService = $waService;
    }

    /**
     * Handle the Order "updated" event.
     * Kirim notifikasi jika status berubah.
     */
    public function updated(Order $order): void
    {
        // Pastikan status berubah
        if ($order->isDirty('status')) {
            $oldStatus = $order->getOriginal('status');
            $newStatus = $order->status;

            Log::channel('whatsapp')->info('Order status changed', [
                'order_id' => $order->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
            ]);

            // Kirim notifikasi ke pelanggan
            $this->sendStatusNotification($order, $newStatus);
        }
    }

    /**
     * Kirim pesan berdasarkan status baru.
     */
    private function sendStatusNotification(Order $order, string $status): void
    {
        $customer = $order->customer;
        if (!$customer || !$customer->phone) {
            Log::channel('whatsapp')->warning('Customer not found for order', ['order_id' => $order->id]);
            return;
        }

        $message = $this->getStatusMessage($order, $status);
        if (!$message) return;

        // Kirim via WhatsAppService
        $result = $this->waService->sendText($customer->phone, $message);

        // Simpan pesan keluar ke database
        if ($result['success'] ?? false) {
            Message::create([
                'customer_id' => $customer->id,
                'order_id' => $order->id,
                'message_id' => $result['message_id'] ?? 'notif_'.time().'_'.uniqid(),
                'from' => env('WHATSAPP_BUSINESS_PHONE', 'system'),
                'to' => $customer->phone,
                'body' => $message,
                'type' => 'text',
                'status' => 'sent',
                'is_incoming' => false,
                'parsed' => true,
                'chat_status' => 'active',
            ]);
        } else {
            Log::channel('whatsapp')->error('Failed to send status notification', [
                'order_id' => $order->id,
                'customer_phone' => $customer->phone,
            ]);
        }
    }

    /**
     * Template pesan untuk setiap status.
     */
    private function getStatusMessage(Order $order, string $status): ?string
    {
        $orderNumber = '#'.$order->id;

        $templates = [
            'processed' => "Halo! Pesanan {$orderNumber} sedang kami proses. Tim kami sedang merangkai buket untukmu. 🎨",
            'completed' => "Selamat! Pesanan {$orderNumber} sudah selesai dan siap dikirim. Kurir akan segera mengambilnya. 🚚",
            'cancelled' => "Pesanan {$orderNumber} kami batalkan. Jika ada pertanyaan, silakan hubungi Admin. 😔",
        ];

        return $templates[$status] ?? null;
    }
}