<?php

/**
 * CONTOH PENGGUNAAN WHATSAPP INTEGRATION
 * 
 * File ini menunjukkan berbagai cara menggunakan WhatsApp integration
 * untuk pengiriman pesan dan media kepada customer.
 */

namespace App\Helpers;

// ============================================
// CONTOH 1: Mengirim pesan saat order dibuat
// ============================================
// Di dalam OrderController atau OrderService:

/*

use App\Services\WhatsAppService;
use App\Models\Order;
use App\Models\Customer;

class OrderController {
    public function create(Request $request)
    {
        $validated = $request->validate([...]);
        
        $customer = Customer::findOrFail($validated['customer_id']);
        $order = Order::create([...]);
        
        // Kirim notifikasi WhatsApp
        $waService = new WhatsAppService();
        $waService->sendText(
            $customer->phone,
            "Pesanan Anda telah diterima. No pesanan: #{$order->id}"
        );
        
        return response()->json(['success' => true]);
    }
}

*/

// ============================================
// CONTOH 2: Mengirim invoice/bukti via media
// ============================================

/*

use App\Services\WhatsAppService;
use Barryvdh\DomPDF\Facade\Pdf;

class OrderService {
    public function sendInvoice(Order $order)
    {
        // Generate PDF
        $pdf = Pdf::loadView('invoices.order', ['order' => $order]);
        $pdfPath = storage_path('app/invoices/order-' . $order->id . '.pdf');
        $pdf->save($pdfPath);
        
        // Kirim via WhatsApp
        $waService = new WhatsAppService();
        $waService->sendMedia(
            $order->customer->phone,
            $pdfPath,
            "Berikut adalah invoice pesanan Anda"
        );
        
        return true;
    }
}

*/

// ============================================
// CONTOH 3: Notification Job
// ============================================

/*

use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\SerializesModels;

class SendWhatsAppNotification implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        private int $customerId,
        private string $message
    ) {}

    public function handle(WhatsAppService $whatsappService)
    {
        $customer = Customer::find($this->customerId);
        if ($customer) {
            $whatsappService->sendText($customer->phone, $this->message);
        }
    }
}

// Usage:
// dispatch(new SendWhatsAppNotification($customer->id, 'Pesanan Anda sudah disiapkan'));

*/

// ============================================
// CONTOH 4: Event Listener
// ============================================

/*

use App\Events\OrderConfirmed;
use App\Services\WhatsAppService;
use Illuminate\Events\Dispatcher;

class NotifyOrderConfirmation
{
    public function handle(OrderConfirmed $event, WhatsAppService $waService)
    {
        $waService->sendText(
            $event->order->customer->phone,
            "Pesanan #{$event->order->id} telah dikonfirmasi. "
            . "Estimasi selesai: {$event->order->estimated_completion_date}"
        );
    }
}

// Register di EventServiceProvider:
// protected $listen = [
//     OrderConfirmed::class => [
//         NotifyOrderConfirmation::class,
//     ],
// ];

*/

// ============================================
// CONTOH 5: Using FormRequest untuk validation
// ============================================

/*

use App\Http\Requests\SendWhatsAppTextRequest;
use App\Models\Customer;
use Illuminate\Http\Request;

class NotificationController
{
    public function sendBulkMessage(Request $request)
    {
        $request->validate([
            'customer_ids' => 'required|array',
            'customer_ids.*' => 'exists:customers,id',
            'message' => 'required|string|max:1024'
        ]);
        
        $customers = Customer::whereIn('id', $request->customer_ids)->get();
        
        foreach ($customers as $customer) {
            // Kirim via endpoint
            $response = Http::post(route('whatsapp.send-text'), [
                'customer_id' => $customer->id,
                'message' => $request->message
            ]);
        }
        
        return response()->json(['sent' => count($customers)]);
    }
}

*/

// ============================================
// CONTOH 6: Handling incoming messages
// ============================================

/*

// Webhook handler sudah ada di WebhookController
// Message akan disimpan otomatis ke database

// Untuk custom processing, tambahkan ke ParseWhatsAppMessage listener:

namespace App\Listeners;

use App\Events\WhatsAppMessageReceived;
use App\Models\Message;

class ProcessWhatsAppKeywords
{
    public function handle(WhatsAppMessageReceived $event)
    {
        $message = $event->message;
        $body = strtolower($message->body);
        
        // Deteksi keyword
        if (strpos($body, 'menu') !== false) {
            // Kirim menu list
        } elseif (strpos($body, 'harga') !== false) {
            // Kirim price list
        }
        
        $message->update(['parsed' => true]);
    }
}

*/

// ============================================
// CONTOH 7: Testing
// ============================================

/*

use Illuminate\Support\Facades\Http;

class WhatsAppIntegrationTest extends TestCase
{
    public function test_send_text_message()
    {
        $customer = Customer::factory()->create(['phone' => '628123456789']);
        
        $response = $this->post('/api/whatsapp/send-text', [
            'customer_id' => $customer->id,
            'message' => 'Test message'
        ]);
        
        $response->assertStatus(201);
        $response->assertJsonStructure(['success', 'message_id']);
    }
    
    public function test_webhook_receives_message()
    {
        Http::fake();
        
        $response = $this->post('/api/whatsapp/webhook', 
            [
                'type' => 'text',
                'from' => '628123456789',
                'content' => 'Incoming message'
            ],
            ['x-api-key' => env('WHATSAPP_WEBHOOK_KEY')]
        );
        
        $response->assertStatus(200);
        $this->assertDatabaseHas('messages', [
            'body' => 'Incoming message'
        ]);
    }
}

*/

// ============================================
// CONTOH 8: Broadcasting ke Frontend
// ============================================

/*

// Jika ingin real-time update ke frontend:

use Illuminate\Broadcasting\Channel;

class MessageBroadcaster
{
    public function broadcast(Message $message)
    {
        broadcast(new MessageReceived($message))
            ->toOthers();
    }
}

// Di frontend (Vue/React):
// Echo.private(`conversation.${conversationId}`)
//     .listen('MessageReceived', (data) => {
//         console.log('New message:', data);
//     });

*/

// ============================================
// CHECKLIST IMPLEMENTASI
// ============================================

/*

SETUP:
[ ] Copy wa-service ke folder / install dependencies (npm install)
[ ] Update .env Laravel dengan WHATSAPP_* config
[ ] Update .env wa-service dengan API_KEY dan LARAVEL_WEBHOOK_URL
[ ] Jalankan migration: php artisan migrate
[ ] Jalankan wa-service: npm start

TESTING:
[ ] Scan QR code di wa-service terminal
[ ] Test webhook: GET /api/whatsapp/webhook/test
[ ] Test status: GET /api/whatsapp/status
[ ] Kirim test message via: POST /api/whatsapp/send-text
[ ] Cek log: tail -f storage/logs/whatsapp.log

PRODUCTION:
[ ] Set WHATSAPP_SERVICE_URL ke domain production
[ ] Set APP_ENV=production
[ ] Sesuaikan WHATSAPP_API_KEY dan WHATSAPP_WEBHOOK_KEY
[ ] Backup auth folder wa-service
[ ] Setup monitoring untuk wa-service uptime

*/

class WhatsAppIntegrationExamples
{
    // Placeholder class untuk dokumentasi
}
