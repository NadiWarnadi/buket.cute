<?php

namespace App\Listeners;

use App\Events\WhatsAppMessageReceived;
use App\Models\Customer;
use App\Models\Message;
use App\Services\Chatbot\ConversationManager;
use App\Services\Chatbot\IntentClassifier;
use App\Services\Chatbot\OrderFlowManager;
use App\Services\Chatbot\ReplySender;
use App\Services\FuzzyBotService;
use App\Services\OrderDraftService;
use App\Services\PaymentManager;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessMessageWithFuzzyBot
{
    protected FuzzyBotService $fuzzyBotService;
    protected WhatsAppService $whatsappService;
    protected OrderDraftService $orderDraftService;
    protected IntentClassifier $intentClassifier;
    protected ReplySender $replySender;
    protected PaymentManager $paymentManager;

    public function __construct(
        FuzzyBotService $fuzzyBotService,
        WhatsAppService $whatsappService,
        OrderDraftService $orderDraftService,
        IntentClassifier $intentClassifier,
        ReplySender $replySender,
        PaymentManager $paymentManager
    ) {
        $this->fuzzyBotService = $fuzzyBotService;
        $this->whatsappService = $whatsappService;
        $this->orderDraftService = $orderDraftService;
        $this->intentClassifier = $intentClassifier;
        $this->replySender = $replySender;
        $this->paymentManager = $paymentManager;
    }

    public function handle(WhatsAppMessageReceived $event): void
    {
        $message = $event->message;
        $payload = $event->payload;    // <-- array dari webhook controller
        $traceId = uniqid('trace_', true);
     

        // 1. Cegah pemrosesan pesan dari nomor bisnis sendiri
        if ($message->from === config('services.whatsapp.business_phone')) {
            return;
        }

        // 2. Lock untuk mencegah duplikasi
        $processed = DB::transaction(function () use ($message) {
            $fresh = Message::where('id', $message->id)->lockForUpdate()->first();
            if (!$fresh || $fresh->parsed) {
                return false;
            }
            $fresh->update(['parsed' => true, 'parsed_at' => now()]);
            return true;
        });

        if (!$processed) {
            Log::channel('whatsapp')->warning('Duplicate message skipped', ['id' => $message->id]);
            return;
        }

        // 4. Dapatkan customer
        $customer = Customer::where('phone', $message->from)->first();
        if (!$customer) {
            $customer = Customer::create(['phone' => $message->from]);
        }
        $message->update(['customer_id' => $customer->id]);

        // 5. Cek jika ini adalah bukti pembayaran (image message)
        if ($message->type === 'image') {
            $this->handlePaymentProof($message, $customer);
            return; // Jangan proses sebagai pesan teks biasa
        }

        // 6. Hanya proses tipe yang relevan
        if (!in_array($message->type, ['text', 'conversation', 'chat', 'extendedtext']) || empty($message->body)) {
            return;
        }

        // 5. Inisialisasi service‑service utama
        $conv = new ConversationManager($customer);
        $userMessage = trim($message->body);
        $intent = $this->intentClassifier->classify($userMessage);

        // 6. Jika sedang ditangani admin, jangan proses bot
        if ($conv->isAdminHandled()) {
            Log::channel('whatsapp')->info('Admin takeover active, bot skipped', ['customer' => $customer->phone]);
            return;
        }

        Log::channel('whatsapp')->info('Processing message', [
            'customer' => $customer->phone,
            'intent' => $intent,
            'state' => $conv->getState(),
            'message' => $userMessage,
        ]);

        // 7. Route berdasarkan intent dan state
        $state = $conv->getState();
        $orderStates = [
            ConversationManager::STATE_ORDERING_NAME,
            ConversationManager::STATE_ORDERING_PRODUCT,
            ConversationManager::STATE_ORDERING_QUANTITY,
            ConversationManager::STATE_ORDERING_ADDRESS,
             ConversationManager::STATE_ORDERING_PAYMENT, 
            ConversationManager::STATE_ORDERING_CONFIRMING,
        ];

        // Jika sedang dalam konfirmasi komplain
        if ($state === ConversationManager::STATE_COMPLAINT_CONFIRMING) {
            $this->handleComplaintConfirmation($conv, $customer, $userMessage);
            return;
        }

        if (in_array($state, $orderStates)) {
    // Jika state adalah bagian dari order flow, gunakan OrderFlowManager
            $this->handleOrder($conv, $customer, $userMessage);
            return;
        }

        switch ($intent) {
    case 'greeting':
        $this->handleGreeting($conv, $customer, $payload);
        break;
    
    case 'status':   // <-- tambah case ini
        $this->handleStatusCheck($customer);
        break;

    case 'order':
        $this->handleOrder($conv, $customer, $userMessage);
        break;

    case 'confirm':
    case 'cancel':
        $this->handleConfirmationOrCancel($conv, $customer, $userMessage, $intent);
        break;
    
    case 'complaint':
         $this->handleComplaint($conv, $customer, $userMessage);
    break;

    case 'help':
        $this->handleHelp($conv, $customer);
        break;

    default:
        $this->handleFaqOrFallback($conv, $customer, $userMessage);
        break;
}
    }

    /**
     * Tangani sapaan: reset state dan kirim balasan ramah.
     */
    protected function handleGreeting(ConversationManager $conv, Customer $customer, array $payload): void
    {
        $conv->reset();
        // Hapus draft jika ada
        $draft = $this->orderDraftService->getCustomerActiveDraft($customer);
        if ($draft) {
            $draft->delete();
        }
        
        Log::channel('whatsapp')->debug('Greeting payload', [
            'payload' => $payload,
            'pushname' => $payload['pushname'] ?? 'MISSING'
        ]);
        // Ambil pushname dari payload (dikirim oleh Node.js)
        $displayName = $payload['pushname'] ?? 'Kak';
        $reply = "Halo, {$displayName}! Selamat datang di Buket Cute. Ada yang bisa saya bantu? 😊";
        $this->replySender->send($customer, $reply);
    }

    /**
     * Tangani intent pemesanan: delegasikan ke OrderFlowManager (nantinya).
     * Untuk sementara, gunakan logika lama sebagai fallback.
     */
    protected function handleOrder(ConversationManager $conv, Customer $customer, string $message): void
{
    $orderFlow = app(OrderFlowManager::class, ['conv' => $conv]);
    $orderFlow->process($message, $customer);
}

    /**
     * Tangani konfirmasi atau pembatalan saat dalam state confirming.
     */
    protected function handleConfirmationOrCancel(ConversationManager $conv, Customer $customer, string $message, string $intent): void
    {
        $state = $conv->getState();
        if ($state !== ConversationManager::STATE_ORDERING_CONFIRMING) {
            // Jika tidak dalam state confirming, abaikan
            $this->handleFaqOrFallback($conv, $customer, $message);
            return;
        }

        $result = $this->fuzzyBotService->processOrderConfirmation($message, $customer);

        if ($result['matched'] && !empty($result['response'])) {
            $this->replySender->send($customer, $result['response']);
            if ($result['action'] === 'order_created') {
                $conv->reset(); // Pesanan selesai
            } else {
                $conv->setState($result['next_context'] ?? null);
            }
        } else {
            $this->replySender->send($customer, "Ketik 'iya' untuk lanjut atau 'ubah' untuk mengubah data.");
        }
    }

    /**
     * Tangani permintaan bantuan / admin.
     */
    protected function handleHelp(ConversationManager $conv, Customer $customer): void
    {
        // Aktifkan admin takeover (nantinya admin akan tangani via panel)
        $conv->setAdminHandled(true);
        $this->replySender->send($customer, "Baik, sebentar ya Kak. Admin kami akan segera membantu. 🙏");
        // TODO: Kirim notifikasi ke admin
    }

    /**
     * Tangani FAQ atau fallback.
     */
    protected function handleFaqOrFallback(ConversationManager $conv, Customer $customer, string $message): void
    {
        $currentContext = $conv->getState();
        $result = $this->fuzzyBotService->processMessageWithContext($message, $currentContext);

        if ($result['matched'] && !empty($result['response'])) {
            $this->replySender->send($customer, $result['response']);
            if (isset($result['next_context'])) {
                $conv->setState($result['next_context']);
            }
        } else {
            // Fallback jika tidak ada yang cocok
            $fallback = "Maaf, saya belum mengerti. Bisa tanyakan hal lain atau ketik 'bantuan' untuk admin.";
            $this->replySender->send($customer, $fallback);
        }
    }

    /**
 * Handle cek status pesanan
 */
protected function handleStatusCheck(Customer $customer): void
{
    // Cari order terakhir customer (selain status 'completed' atau 'cancelled'? Sesuaikan)
    $lastOrder = $customer->orders()
        ->with('items.product')
        ->latest()
        ->first();

    if (!$lastOrder) {
        $this->replySender->send($customer, "Kak, belum ada pesanan atas nama Kakak. Yuk pesan dulu dengan ketik 'pesan' 😊");
        return;
    }

    // Mapping status ke bahasa yang lebih ramah
    $statusText = [
        'pending' => '⏳ menunggu konfirmasi admin',
        'processing' => '🔄 sedang diproses',
        'shipped' => '🚚 dalam pengiriman',
        'delivered' => '✅ sudah sampai tujuan',
        'cancelled' => '❌ dibatalkan',
    ][$lastOrder->status] ?? $lastOrder->status;

    // Format detail pesanan
    $items = [];
    foreach ($lastOrder->items as $item) {
        $items[] = "- {$item->product->name} x{$item->quantity} = Rp " . number_format($item->subtotal, 0, ',', '.');
    }
    $itemList = empty($items) ? "Tidak ada item" : implode("\n", $items);

    $reply = "📦 *Status Pesanan #{$lastOrder->id}*\n\n" .
             "{$itemList}\n\n" .
             "Status: {$statusText}\n\n" .
             "Tanggal: " . $lastOrder->created_at->format('d/m/Y H:i') . "\n\n" .
             "Admin akan segera update ya. Terima kasih 🙏";

    $this->replySender->send($customer, $reply);
}

    /**
     * Tangani bukti pembayaran (image message)
     */
    protected function handlePaymentProof(Message $message, Customer $customer): void
    {
        // Cari order pending payment untuk customer ini
        $pendingOrder = $customer->orders()
            ->where('payment_method', 'bank_transfer')
            ->where('payment_status', 'pending')
            ->latest()
            ->first();

        if (!$pendingOrder) {
            // Tidak ada order pending, kirim pesan bahwa bukti tidak diharapkan
            $this->replySender->send($customer, "Terima kasih telah mengirim gambar. Jika ini bukti pembayaran, silakan lakukan pemesanan terlebih dahulu atau hubungi admin untuk bantuan.");
            return;
        }

        // Proses bukti pembayaran
        $result = $this->paymentManager->processPaymentProof($message, $pendingOrder);

        if ($result['success']) {
            $confirmationMessage = "✅ *Bukti pembayaran diterima!*\n\n" .
                                 "Pesanan Anda sedang diverifikasi oleh admin.\n" .
                                 "Status pembayaran akan diupdate dalam 1-2 jam.\n\n" .
                                 "Terima kasih telah berbelanja di Buket Cute! 🌸";

            $this->replySender->send($customer, $confirmationMessage);

            Log::channel('whatsapp')->info('Payment proof processed successfully', [
                'customer_id' => $customer->id,
                'order_id' => $pendingOrder->id,
                'message_id' => $message->id,
            ]);
        } else {
            $this->replySender->send($customer, "❌ " . $result['error'] . "\n\nSilakan kirim ulang bukti pembayaran yang jelas atau hubungi admin untuk bantuan.");

            Log::channel('whatsapp')->warning('Payment proof processing failed', [
                'customer_id' => $customer->id,
                'order_id' => $pendingOrder->id,
                'message_id' => $message->id,
                'error' => $result['error'],
            ]);
        }
    }
    /**
 * Handle complaint: cek apakah customer pernah order, lalu konfirmasi order terakhir
 */
protected function handleComplaint(ConversationManager $conv, Customer $customer, string $message): void
{
    // Cek apakah customer sudah pernah order
    $lastOrder = $customer->orders()->latest()->first();
    
    if (!$lastOrder) {
        // Belum pernah order
        $this->replySender->send($customer, "Maaf Kak, sepertinya Kakak belum pernah pesan di Buket Cute. Kalau ada kendala atau pertanyaan, silakan cerita ya. Atau ketik 'pesan' untuk mulai memesan.");
        $conv->reset();
        return;
    }

    // Simpan pesan komplain sementara ke order draft
    $draft = $this->orderDraftService->getOrCreateDraft($customer);
    $draft->data = array_merge($draft->data ?? [], [
        'complaint_message' => $message,
        'complaint_order_id' => $lastOrder->id,
    ]);
    $draft->step = ConversationManager::STATE_COMPLAINT_CONFIRMING;
    $draft->save();

    // Set state
    $conv->setState(ConversationManager::STATE_COMPLAINT_CONFIRMING);

    // Kirim konfirmasi ke customer
    $orderDate = $lastOrder->created_at->format('d/m/Y');
    $productNames = $lastOrder->items->map(fn($item) => $item->product->name)->implode(', ');
    $reply = "Maaf sekali atas ketidaknyamanannya, Kak 🙏.\n\n" .
             "Apakah keluhan ini terkait pesanan terakhir Kakak pada *{$orderDate}* dengan produk: *{$productNames}*?\n\n" .
             "Ketik 'iya' jika benar, atau 'tidak' jika keluhan untuk pesanan lain.";
    $this->replySender->send($customer, $reply);
}

/**
 * Handle konfirmasi dari customer setelah ditanya order terakhir
 */
protected function handleComplaintConfirmation(ConversationManager $conv, Customer $customer, string $message): void
{
    $lower = strtolower(trim($message));
    $draft = $this->orderDraftService->getCustomerActiveDraft($customer);

    if (!$draft || empty($draft->data['complaint_message'])) {
        // Draft tidak valid, reset
        $conv->reset();
        $this->replySender->send($customer, "Maaf, terjadi kesalahan. Silakan ulangi komplain Anda.");
        return;
    }

    if (in_array($lower, ['iya', 'ya', 'betul', 'benar', 'setuju'])) {
        // Customer konfirmasi order tersebut
        $orderId = $draft->data['complaint_order_id'] ?? null;
        $complaintMessage = $draft->data['complaint_message'];

        // Simpan complaint ke database
        $complaint = \App\Models\Complaint::create([
            'customer_id' => $customer->id,
            'order_id' => $orderId,
            'message' => $complaintMessage,
            'status' => 'open',
        ]);

        // Hapus draft
        $draft->delete();
        $conv->reset();

        // Kirim respon
        $reply = "Baik, keluhan Kakak sudah kami catat dengan ID #{$complaint->id}.\n" .
                 "Admin kami akan segera menghubungi maksimal 1 jam. Terima kasih kesabarannya 🙏";
        $this->replySender->send($customer, $reply);
        
        // TODO: Kirim notifikasi ke admin (bisa via dashboard atau WhatsApp)
    } 
    elseif (in_array($lower, ['tidak', 'bukan', 'ngga', 'gak'])) {
        // Bukan order terakhir, simpan tanpa order_id
        $complaintMessage = $draft->data['complaint_message'];
        $draft->delete();
        $conv->reset();

        $complaint = \App\Models\Complaint::create([
            'customer_id' => $customer->id,
            'order_id' => null,
            'message' => $complaintMessage,
            'status' => 'open',
        ]);

        $reply = "Baik, keluhan Kakak sudah kami catat dengan ID #{$complaint->id}.\n" .
                 "Admin kami akan segera menghubungi maksimal 1 jam. Terima kasih 🙏";
        $this->replySender->send($customer, $reply);
    } 
    else {
        // Jawaban tidak jelas, ulangi pertanyaan
        $this->replySender->send($customer, "Maaf, saya tidak mengerti. Ketik 'iya' jika keluhan untuk pesanan terakhir, atau 'tidak' jika untuk pesanan lain.");
    }
}
}