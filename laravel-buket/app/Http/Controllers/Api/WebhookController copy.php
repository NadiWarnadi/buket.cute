<?php

namespace App\Http\Controllers\Api;

use App\Events\WhatsAppMessageReceived;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    /**
     * Receive incoming WhatsApp messages from wa-service
     */
    public function handleWhatsAppMessage(Request $request)
    {
        // Validate API Key
        $apiKey = $request->header('x-api-key');
        if ($apiKey !== env('WHATSAPP_WEBHOOK_KEY')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        try {
            // Validate required fields - more flexible since wa-service sends varying formats
            $validated = $request->validate([
                'type' => 'required|string',
                'from' => 'nullable|string',
                'sender_number' => 'nullable|string',
                'body' => 'nullable|string',
                'content' => 'nullable|string',
                'message' => 'nullable|string',
                'caption' => 'nullable|string',
                'media' => 'nullable|array',
                'isGroup' => 'nullable|boolean',
                'timestamp' => 'nullable|integer',
                'message_id' => 'nullable|string',
            ]);

            // Extract phone number - try all possible sources
             $phoneNumber = preg_replace('/[^0-9+]/', '', 
                        ($validated['sender_number'] 
                        ?? $validated['from'] 
                        ?? $request->input('from') ?? ''));
            
            $messageBody = $validated['body'] 
                ?? $validated['caption'] 
                ?? $validated['content'] 
                ?? $validated['message'] 
                ?? '';

            $finalType = $validated['type'];    
            $pushName = $validated['pushname'] ?? $request->input('pushname') ?? null;

               // 3. Filter Tipe Protocol (Agar tidak menuhi database)
            if (in_array($finalType, ['protocol', 'sender_data', 'read_receipt'])) {
                return response()->json(['success' => true, 'message' => 'Ignored protocol message'], 200);
            }

             // 4. CEK DUPLIKASI (PENTING: Jangan cek jika ID-nya kosong)
            $rawMessageId = $validated['message_id'] ?? $request->input('message_id');
            
            if (!empty($rawMessageId)) {
                $existingMessage = Message::where('message_id', $rawMessageId)->first();
                if ($existingMessage) {
                    return response()->json(['success' => true, 'message' => 'Duplicate message ignored'], 200);
                }
            }

             // Tentukan ID final (Pakai ID asli WA atau buat ID unik string)
            $finalMessageId = $rawMessageId ?: 'msg_' . time() . '_' . uniqid();

            // Get or create customer
            $customer = Customer::firstOrCreate(
            ['phone' => $phoneNumber],
            ['name' => null, 'phone' => $phoneNumber]
             );
            if ($pushName) {
    $validated['pushname'] = $pushName;
}
            $allowedMedia = ['image', 'video', 'document', 'audio', 'conversation', 'chat', 'text'];
            // Only create message if type is text or has body content
            $mediaUrl = null;
            $fileName = null;
            $mimeType = null;
            $mediaSize = null;
            Log::channel('whatsapp')->debug('Pushname check', [
                'from_validated' => $validated['pushname'] ?? null,
                'from_request_input' => $request->input('pushname'),
                'final_pushname' => $pushName,
                'all_request_data' => $request->all(),
            ]);

            if (in_array($finalType, ['image', 'video', 'document', 'audio'])) {
            $raw = $validated['raw_message'] ?? [];
            $msgContent = $raw['message'] ?? [];
            $mediaKey = $finalType . 'Message';
             if (isset($msgContent[$mediaKey])) {
            $media = $msgContent[$mediaKey];
            $mediaUrl = $media['url'] ?? null;
            $mimeType = $media['mimetype'] ?? null;
            $fileName = $media['filename'] ?? ($media['caption'] ?? ucfirst($finalType) . ' file');
            $mediaSize = $media['fileLength'] ?? null;
            
        // Jika ada caption di media, gunakan sebagai body (opsional)
        if (!empty($media['caption']) && empty($messageBody)) {
            $messageBody = $media['caption'];
        }
    }
}

            //cek apakah ada yanag terkait dengan fuzyy rule 
            if (! empty($messageBody) || in_array($finalType, $allowedMedia)) {
                // Create message directly (no conversation table)
                $message = Message::create([
                    'customer_id' => $customer->id,
                    'message_id' => $finalMessageId,
                    'from' => $phoneNumber,
                    'to' => env('WHATSAPP_BUSINESS_PHONE', 'system'),
                    'body' => $messageBody ?: '['.strtoupper($validated['type']).' Media]',
                    'type' => $finalType,
                    
                    'status' => 'pending',
                    'is_incoming' => true,
                    'parsed' => false,
                    'chat_status' => 'active',

                     'media_url'    => $mediaUrl,
                    'file_name'    => $fileName,
                    'mime_type'    => $mimeType,
                    'media_size'   => $mediaSize,
                ]);

                Log::channel('whatsapp')->info('Dispatching WhatsAppMessageReceived event', [
                    'db_id' => $message->id,
                    'wa_id' => $finalMessageId,
                    'from' => $phoneNumber,
                    'body' => $message->body,
                ]);

                WhatsAppMessageReceived::dispatch($message, $validated);

                Log::channel('whatsapp')->info('Incoming message processed', [
                    'db_id' => $message->id,
                    'wa_id' => $finalMessageId,
                    'from' => $phoneNumber,
                ]);

              return response()->json([
                'success' => true,
                'message_id' => $message->id,
                'customer_id' => $customer->id,
                ], 200);
             }
             
            // Log if no message was created
            Log::channel('whatsapp')->debug('Webhook received but no message created', [
                'phone' => $phoneNumber,
                'type' => $validated['type'],
                'body' => $messageBody,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Webhook received but no message created',
                'customer_id' => $customer->id,
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::channel('whatsapp')->error('Validation error in webhook', $e->errors());

            return response()->json(['error' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::channel('whatsapp')->error('Error processing webhook', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    /**
     * Test webhook endpoint
     */
    public function testWebhook(Request $request)
    {
        $apiKey = $request->header('x-api-key');
        if ($apiKey !== env('WHATSAPP_WEBHOOK_KEY')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return response()->json([
            'status' => 'ok',
            'message' => 'Webhook is working correctly',
            'timestamp' => now(),
        ]);
    }
}
