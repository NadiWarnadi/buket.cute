<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\PaymentManager;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MidtransWebhookController extends Controller
{
    protected PaymentManager $paymentManager;
    protected WhatsAppService $whatsappService;

    public function __construct(PaymentManager $paymentManager, WhatsAppService $whatsappService)
    {
        $this->paymentManager = $paymentManager;
        $this->whatsappService = $whatsappService;
    }

    public function handleNotification(Request $request)
    {
        if (!$this->verifySignature($request)) {
            Log::warning('Midtrans webhook: invalid signature', $request->all());
            return response()->json(['status' => 'error', 'message' => 'Invalid signature'], 403);
        }

        $notif = $request->all();
        $orderIdRaw = $notif['order_id'] ?? null;
        $transactionStatus = $notif['transaction_status'] ?? null;
        $fraudStatus = $notif['fraud_status'] ?? 'accept';

        if (!$orderIdRaw) {
            Log::error('Midtrans webhook: no order_id');
            return response()->json(['status' => 'error', 'message' => 'No order_id'], 400);
        }

        $parts = explode('-', $orderIdRaw);
        if (count($parts) < 2) {
            Log::error('Midtrans webhook: invalid order_id format', ['order_id' => $orderIdRaw]);
            return response()->json(['status' => 'error', 'message' => 'Invalid order_id format'], 400);
        }
        $realOrderId = intval($parts[1]);

        $order = Order::with('customer')->find($realOrderId);
        if (!$order) {
            Log::error('Midtrans webhook: order not found', ['order_id_raw' => $orderIdRaw, 'real_id' => $realOrderId]);
            return response()->json(['status' => 'error', 'message' => 'Order not found'], 404);
        }

        $paymentStatus = $this->mapTransactionStatus($transactionStatus, $fraudStatus);

        // Update status pembayaran
        $this->paymentManager->updatePaymentStatus($order, $paymentStatus, [
            'transaction_id' => $notif['transaction_id'],
            'transaction_status' => $transactionStatus,
            'raw_notification' => $notif,
        ]);

        // Update status pesanan ke processing jika pembayaran sukses
        if ($paymentStatus === 'paid' && $order->status === 'pending') {
            $order->update(['status' => 'processing']);
        }

        // ========== NOTIFIKASI WHATSAPP OTOMATIS ==========
        if ($paymentStatus === 'paid' && $order->customer && $order->customer->phone) {
            $this->sendPaymentSuccessNotification($order);
        }

        Log::info('Midtrans webhook processed', [
            'order_id' => $order->id,
            'transaction_status' => $transactionStatus,
            'payment_status' => $paymentStatus,
        ]);

        return response()->json(['status' => 'ok']);
    }

    /**
     * Kirim notifikasi WhatsApp ke pelanggan setelah pembayaran sukses.
     */
    protected function sendPaymentSuccessNotification(Order $order): void
    {
        $phone = $order->customer->phone;
        $orderId = $order->id;
        $total = number_format($order->total_price, 0, ',', '.');
        $productNames = $order->items->pluck('product.name')->implode(', ') ?: 'pesanan';

        $message = "✅ *Pembayaran Berhasil Diterima!*\n\n"
                 . "Halo Kak, terima kasih sudah melakukan pembayaran.\n\n"
                 . "📦 Pesanan #{$orderId}\n"
                 . "🛍  Produk: {$productNames}\n"
                 . "💰 Total: Rp {$total}\n\n"
                 . "Pesanan Kakak sedang kami proses dan akan segera dikirim.\n"
                 . "Butuh bantuan? Ketik *cek status* atau hubungi admin.\n\n"
                 . "Terima kasih telah berbelanja di Buket Cute! 🌸";

        try {
            $this->whatsappService->sendText($phone, $message);
            Log::info('WhatsApp payment notification sent', ['order_id' => $orderId, 'phone' => $phone]);
        } catch (\Exception $e) {
            Log::error('Failed to send WhatsApp payment notification', [
                'order_id' => $orderId,
                'error' => $e->getMessage()
            ]);
        }
    }

    protected function verifySignature(Request $request): bool
    {
        $serverKey = config('services.midtrans.server_key');
        $orderId = $request->input('order_id');
        $statusCode = $request->input('status_code');
        $grossAmount = $request->input('gross_amount');
        $signatureKey = $request->input('signature_key');

        $expectedSignature = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);

        return hash_equals($expectedSignature, $signatureKey);
    }

    protected function mapTransactionStatus(string $transactionStatus, string $fraudStatus): string
    {
        if ($transactionStatus === 'capture') {
            return $fraudStatus === 'accept' ? 'paid' : 'failed';
        } elseif ($transactionStatus === 'settlement') {
            return 'paid';
        } elseif (in_array($transactionStatus, ['pending', 'authorize'])) {
            return 'pending';
        } elseif (in_array($transactionStatus, ['deny', 'cancel', 'expire', 'failure'])) {
            return 'failed';
        }
        return 'pending';
    }
}