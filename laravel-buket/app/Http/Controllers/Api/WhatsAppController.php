<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SendWhatsAppTextRequest;
use App\Http\Requests\SendWhatsAppMediaRequest;
use App\Models\Customer;
use App\Models\Message;
use App\Models\Media;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class WhatsAppController extends Controller
{
    protected WhatsAppService $whatsappService;

    public function __construct(WhatsAppService $whatsappService)
    {
        $this->whatsappService = $whatsappService;
    }

    /**
     * Cek status koneksi WhatsApp
     * GET /api/whatsapp/status
     */
    public function status()
    {
        try {
            $status = $this->whatsappService->getStatus();
            
            if ($status['success']) {
                return response()->json($status, 200);
            }
            
            return response()->json($status, 500);
        } catch (\Exception $e) {
            Log::channel('whatsapp')->error('Error checking status', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Kirim pesan teks ke customer
     * POST /api/whatsapp/send-text
     * 
     * Expected payload:
     * {
     *   "customer_id": 1,
     *   "message": "Halo! Pesanan Anda sudah siap",
     *   "order_id": null (optional)
     * }
     */
    public function sendText(SendWhatsAppTextRequest $request)
    {
        try {
            $validated = $request->validated();

            $customer = Customer::findOrFail($validated['customer_id']);

            if (empty($customer->phone)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Customer phone number not set'
                ], 422);
            }

            // Kirim pesan ke WhatsApp
            $result = $this->whatsappService->sendText($customer->phone, $validated['message']);

            if (!$result['success']) {
                return response()->json($result, 500);
            }

            // Simpan pesan
            $message = Message::create([
                'customer_id' => $customer->id,
                'order_id' => $validated['order_id'] ?? null,
                'message_id' => $result['message_id'] ?? 'msg_' . time(),
                'from' => env('WHATSAPP_BUSINESS_PHONE', 'system'),
                'to' => $customer->phone,
                'body' => $validated['message'],
                'type' => 'text',
                'status' => 'sent',
                'is_incoming' => false,
                'parsed' => true,
                'parsed_at' => now(),
                'chat_status' => 'active',
            ]);

            Log::channel('whatsapp')->info('Message sent', ['message_id' => $message->id]);

            return response()->json([
                'success' => true,
                'message_id' => $message->id,
                'customer_id' => $customer->id,
            ], 201);

        } catch (ValidationException $e) {
            Log::channel('whatsapp')->warning('Validation error in send text', $e->errors());
            return response()->json(['error' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::channel('whatsapp')->error('Error sending text message', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Kirim media (gambar, video, dokumen) ke customer
     * POST /api/whatsapp/send-media
     * 
     * Form data:
     * - customer_id: required, integer
     * - file: required, file
     * - caption: optional, string
     * - order_id: optional, integer
     */
    public function sendMedia(SendWhatsAppMediaRequest $request)
    {
        try {
            $validated = $request->validated();

            $customer = Customer::findOrFail($validated['customer_id']);

            if (empty($customer->phone)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Customer phone number not set'
                ], 422);
            }

            $file = $request->file('file');
            $filePath = $file->store('whatsapp-uploads', 'local');
            $fullPath = storage_path('app/' . $filePath);

            // Kirim media ke WhatsApp
            $result = $this->whatsappService->sendMedia(
                $customer->phone,
                $fullPath,
                $validated['caption'] ?? null
            );

            if (!$result['success']) {
                // Hapus file jika gagal
                @unlink($fullPath);
                return response()->json($result, 500);
            }

            // Dapatkan atau buat conversation (1 customer = 1 conversation only)
    

            // Tentukan media type
            $mimeType = $file->getMimeType();
            $mediaType = $this->getMimeTypeCategory($mimeType);

            // Simpan pesan media ke database
            $message = Message::create([
            
                'customer_id' => $customer->id,
                'order_id' => $validated['order_id'] ?? null,
                'message_id' => $result['message_id'] ?? 'msg_' . time(),
                'from' => env('WHATSAPP_BUSINESS_PHONE', 'system'),
                'to' => $customer->phone,
                'body' => $validated['caption'] ?? '[' . strtoupper($mediaType) . ']',
                'type' => $mediaType,
                'status' => 'sent',
                'is_incoming' => false,
                'parsed' => true,
                'parsed_at' => now()
            ]);

            // Simpan info media
            $media = Media::create([
                'message_id' => $message->id,
                'file_path' => $filePath,
                'file_name' => $file->getClientOriginalName(),
                'file_type' => $mediaType,
                'file_size' => $file->getSize(),
                'mime_type' => $mimeType
            ]);

           

            Log::channel('whatsapp')->info('Outgoing media message', [
                'message_id' => $message->id,
                'media_id' => $media->id,
                'customer_id' => $customer->id,
                'type' => $mediaType
            ]);

            return response()->json([
                'success' => true,
                'message_id' => $message->id,
                'media_id' => $media->id,
                'customer_id' => $customer->id,
              
            ], 201);

        } catch (ValidationException $e) {
            Log::channel('whatsapp')->warning('Validation error in send media', $e->errors());
            return response()->json(['error' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::channel('whatsapp')->error('Error sending media', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get conversations list (grouped by customer)
     * GET /api/whatsapp/conversations
     * 
     * Query params:
     * - status: optional (active, archived, closed)
     * - limit: optional, default 20
     */
    public function getConversations(Request $request)
    {
        try {
            $status = $request->get('status', 'active');
            $limit = $request->get('limit', 20);

            // Get customers with their messages grouped by customer_id
            $customers = Customer::with(['messages' => function ($query) use ($status) {
                if ($status) {
                    $query->where('chat_status', $status);
                }
                $query->orderByDesc('created_at');
            }])
            ->whereHas('messages', function ($query) use ($status) {
                if ($status) {
                    $query->where('chat_status', $status);
                }
            })
            ->orderByDesc('updated_at')
            ->paginate($limit);

            return response()->json([
                'success' => true,
                'data' => $customers->map(fn($customer) => [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'phone' => $customer->phone,
                    'status' => $customer->getChatStatus(),
                    'last_message' => $customer->getLastMessage(),
                    'message_count' => $customer->messages->count()
                ])
            ], 200);

        } catch (\Exception $e) {
            Log::channel('whatsapp')->error('Error fetching conversations', [
                'message' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get messages dari customer
     * GET /api/whatsapp/customers/{id}/messages
     */
    public function getConversationMessages($customerId, Request $request)
    {
        try {
            $customer = Customer::findOrFail($customerId);
            
            $messages = Message::where('customer_id', $customerId)
                ->orderByDesc('created_at')
                ->paginate($request->get('limit', 50));

            return response()->json([
                'success' => true,
                'customer_id' => $customerId,
                'data' => $messages
            ], 200);

        } catch (\Exception $e) {
            Log::channel('whatsapp')->error('Error fetching conversation messages', [
                'message' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Dapatkan conversation dari customer
     * GET /api/whatsapp/customers/{id}/conversation
     */
    public function getCustomerConversation($customerId)
    {
        try {
            $customer = Customer::with('messages')->findOrFail($customerId);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'phone' => $customer->phone,
                    'status' => $customer->getChatStatus(),
                    'messages' => $customer->messages,
                    'message_count' => $customer->messages->count()
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::channel('whatsapp')->error('Error fetching customer conversation', [
                'message' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper: Tentukan kategori media berdasarkan MIME type
     */
    private function getMimeTypeCategory(string $mimeType): string
    {
        if (strpos($mimeType, 'image') !== false) {
            return 'image';
        } elseif (strpos($mimeType, 'video') !== false) {
            return 'video';
        } elseif (strpos($mimeType, 'audio') !== false) {
            return 'audio';
        }
        return 'document';
    }
}
