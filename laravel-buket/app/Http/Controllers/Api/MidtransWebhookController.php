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
        // 1. Amankan Verifikasi Signature
        try {
            if (!$this->verifySignature($request)) {
                Log::warning('Midtrans webhook: invalid signature', $request->all());
                return response()->json(['status' => 'error', 'message' => 'Invalid signature'], 403);
            }
        } catch (\Exception $e) {
            Log::error('Midtrans webhook signature calculation crashed: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Signature verification failed'], 400);
        }

        $notif = $request->all();
        $orderIdRaw = $notif['order_id'] ?? null;
        $transactionStatus = $notif['transaction_status'] ?? null;
        $fraudStatus = $notif['fraud_status'] ?? 'accept';
        $paymentType = $notif['payment_type'] ?? 'unknown';

        if (!$orderIdRaw) {
            Log::error('Midtrans webhook: no order_id');
            return response()->json(['status' => 'error', 'message' => 'No order_id'], 400);
        }

        // 2. Parsing Order ID dari format "ORDER-15-1779188742" menjadi bigint murni 15
        $parts = explode('-', $orderIdRaw);
        if (count($parts) < 2) {
            Log::error('Midtrans webhook: invalid order_id format', ['order_id' => $orderIdRaw]);
            return response()->json(['status' => 'error', 'message' => 'Invalid order_id format'], 400);
        }
        $realOrderId = intval($parts[1]);

        // 3. Ambil Data Order dengan Eager Loading Relasi
        $order = Order::with(['customer', 'items.product'])->find($realOrderId);
        if (!$order) {
            Log::error('Midtrans webhook: order not found in database', ['order_id_raw' => $orderIdRaw, 'real_id' => $realOrderId]);
            // Balas dengan 200 OK agar Midtrans tidak membombardir server lokalmu berulang kali untuk data ghoib
            return response()->json(['status' => 'ignored', 'message' => 'Order not found'], 200);
        }

        // 4. Proses Transaksi di dalam Block Try-Catch agar Aman dari Error 500
        try {
            $paymentStatus = $this->mapTransactionStatus($transactionStatus, $fraudStatus);
            
            // Catat status awal sebelum di-update untuk pengecekan idempotensi
            $previousPaymentStatus = $order->payment_status; 
            $previousOrderStatus = $order->status;

            // Update metode pembayaran secara dinamis sesuai e-wallet/bank yang dipakai customer
            $order->payment_method = $paymentType;

            // Update status pembayaran melalui service eksternal kamu
            $this->paymentManager->updatePaymentStatus($order, $paymentStatus, [
                'transaction_id'     => $notif['transaction_id'] ?? null,
                'transaction_status' => $transactionStatus,
                'raw_notification'   => $notif,
            ]);

            // Jika status pembayaran benar-benar baru berubah jadi 'paid', update status pesanan
            if ($paymentStatus === 'paid' && $previousPaymentStatus !== 'paid') {
                
                // Pastikan update status pesanan memicu event Eloquent
                $order->update(['status' => 'processing']);

                // Kirim notifikasi WhatsApp sukses bayar
                if ($order->customer && $order->customer->phone) {
                    $this->sendPaymentSuccessNotification($order);
                }
            }

            Log::info('Midtrans webhook processed successfully', [
                'order_id'           => $order->id,
                'transaction_status' => $transactionStatus,
                'payment_status'     => $paymentStatus,
            ]);

            // RESPON WAJIB BAGI MIDTRANS
            return response()->json(['status' => 'ok', 'message' => 'Notification handled successfully'], 200);

        } catch (\Exception $e) {
            // JIKA LOGIKA DI ATAS CRASH / OBSERVER ERROR, KAMU TETAP KASIH 200 KE MIDTRANS AGAR EMAIL WARNING STOP.
            Log::error('Internal Crash pada Webhook Handler (Gagal Diproses):', [
                'order_id' => $realOrderId,
                'error'    => $e->getMessage(),
                'line'     => $e->getLine(),
                'trace'    => $e->getTraceAsString()
            ]);

            return response()->json(['status' => 'error_handled', 'message' => 'Internal processing failed but acknowledged'], 200);
        }
    }

    protected function sendPaymentSuccessNotification(Order $order): void
    {
        $phone = $order->customer->phone;
        $orderId = $order->id;
        $total = number_format($order->total_price, 0, ',', '.');
        
        // Amankan penamaan produk jika item kosong atau relasi bermasalah
        $productNames = $order->items->map(function($item) {
            return $item->product ? $item->product->name : 'Produk';
        })->implode(', ') ?: 'Pesanan';

        $message = "✅ *Pembayaran Berhasil Diterima!*\n\n"
                 . "Halo Kak, terima kasih sudah melakukan pembayaran.\n\n"
                 . "📦 Pesanan #{$orderId}\n"
                 . "🛍  Produk: {$productNames}\n"
                 . "💰 Total: Rp {$total}\n\n"
                 . "Pesanan Kakak sedang kami proses dan akan segera dikirim.\n"
                 . "Butuh bantuan? Ketik *cek status* atau hubungi admin.\n\n"
                 . "Terima kasih telah berbelanja di Buket Cute! 🌸";

        try {
            // Tembak ke Node.js WhatsApp Service
            $this->whatsappService->sendText($phone, $message);
            Log::info('WhatsApp payment notification sent', ['order_id' => $orderId, 'phone' => $phone]);
        } catch (\Exception $e) {
            // Mencegah WhatsApp Down menghentikan proses transaksi database utama
            Log::error('Webhook berhasil diproses tetapi Gagal Mengirim WhatsApp:', [
                'order_id' => $orderId,
                'error'    => $e->getMessage()
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

        if (!$orderId || !$statusCode || !$grossAmount || !$signatureKey) {
            return false;
        }

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