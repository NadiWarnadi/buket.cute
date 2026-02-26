<?php

namespace App\Services;

use App\Models\OutgoingMessage;
use App\Models\Order;
use App\Models\Customer;
use App\Jobs\SendWhatsAppNotification;

class NotificationService
{
    /**
     * Send order confirmation to customer
     */
    public static function notifyOrderCreated(Order $order)
    {
        $customer = $order->customer;
        $itemSummary = $order->items->map(function ($item) {
            $name = $item->product_id ? $item->product->name : $item->custom_description;
            return "{$item->quantity}x {$name}";
        })->implode(', ');

        $body = "✅ Pesanan Anda #" . str_pad($order->id, 5, '0', STR_PAD_LEFT) . " telah diterima.\n\n" .
                "Item: {$itemSummary}\n" .
                "Total: Rp" . number_format($order->total_price, 0, ',', '.') . "\n\n" .
                "Kami akan segera memproses pesanan Anda. Terima kasih!";

        return self::createOutgoingMessage($customer, $order, $body);
    }

    /**
     * Send order status update to customer
     */
    public static function notifyOrderStatusChanged(Order $order)
    {
        $customer = $order->customer;
        $statusLabel = $order->getStatusLabel();

        $messages = [
            'pending' => '⏳ Pesanan sedang menunggu untuk diproses.',
            'processed' => '🚀 Pesanan Anda sedang diproses.',
            'completed' => '✅ Pesanan Anda telah selesai dan siap diambil/dikirim.',
            'cancelled' => '❌ Pesanan Anda telah dibatalkan.',
        ];

        $statusMsg = $messages[$order->status] ?? 'Status pesanan berubah';

        $body = "⚡ Update Pesanan #" . str_pad($order->id, 5, '0', STR_PAD_LEFT) . "\n\n" .
                "Status: {$statusLabel}\n" .
                "{$statusMsg}\n\n" .
                "Terima kasih telah berbelanja di Bucket Cutie!";

        return self::createOutgoingMessage($customer, $order, $body);
    }

    /**
     * Notify admin about new order
     */
    public static function notifyAdminNewOrder(Order $order)
    {
        $body = "🔔 Pesanan Baru!\n\n" .
                "No. Pesanan: #" . str_pad($order->id, 5, '0', STR_PAD_LEFT) . "\n" .
                "Pelanggan: {$order->customer->name}\n" .
                "Telepon: {$order->customer->phone}\n" .
                "Total: Rp" . number_format($order->total_price, 0, ',', '.') . "\n" .
                "Alamat: {$order->customer->address}\n\n" .
                "Silahkan proses pesanan di dashboard.";

        // Send to admin WhatsApp (configured in settings)
        // For now, just create in outgoing_messages for manual review
        \Log::info("Admin notification: {$body}");
    }

    /**
     * Notify admin about low stock
     */
    public static function notifyAdminLowStock($ingredient)
    {
        $body = "⚠️ Stok Menipis!\n\n" .
                "Bahan: {$ingredient->name}\n" .
                "Stok: {$ingredient->stock} {$ingredient->unit}\n" .
                "Min: {$ingredient->min_stock} {$ingredient->unit}\n\n" .
                "Segera lakukan pemesanan!";

        \Log::warning("Low stock notification: {$body}");
    }

    /**
     * Create outgoing message and queue it
     */
    private static function createOutgoingMessage(Customer $customer, Order $order = null, $body)
    {
        try {
            $msg = OutgoingMessage::create([
                'customer_id' => $customer->id,
                'order_id' => $order?->id,
                'to' => $customer->getWhatsAppNumber(),
                'body' => $body,
                'type' => OutgoingMessage::TYPE_TEXT,
                'status' => OutgoingMessage::STATUS_PENDING,
            ]);

            // Queue the job to send
            SendWhatsAppNotification::dispatch($msg);

            return $msg;
        } catch (\Exception $e) {
            \Log::error("Failed to create outgoing message: " . $e->getMessage());
            return null;
        }
    }
}
