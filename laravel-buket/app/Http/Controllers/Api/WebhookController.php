<?php

namespace App\Http\Controllers\Api;

use App\Events\WhatsAppMessageReceived;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Message;
use App\Models\Media;
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
            // Validate required fields
            $validated = $request->validate([
                'type'          => 'required|string',
                'from'          => 'nullable|string',
                'sender_number' => 'nullable|string',
                'body'          => 'nullable|string',
                'content'       => 'nullable|string',
                'message'       => 'nullable|string',
                'caption'       => 'nullable|string',
                'media'         => 'nullable|array',
                'isGroup'       => 'nullable|boolean',
                'timestamp'     => 'nullable|integer',
                'message_id'    => 'nullable|string',
            ]);

            // Extract phone number
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

            // === FLAG FROM ADMIN (dikirim Node.js untuk pesan keluar) ===
            $fromAdmin = $validated['from_admin'] ?? $request->input('from_admin') ?? false;

            // Filter tipe protocol
            if (in_array($finalType, ['protocol', 'sender_data', 'read_receipt'])) {
                return response()->json(['success' => true, 'message' => 'Ignored protocol message'], 200);
            }

            // Cek duplikasi berdasarkan message_id
            $rawMessageId = $validated['message_id'] ?? $request->input('message_id');
            if (!empty($rawMessageId)) {
                $existingMessage = Message::where('message_id', $rawMessageId)->first();
                if ($existingMessage) {
                    return response()->json(['success' => true, 'message' => 'Duplicate message ignored'], 200);
                }
            }

            $finalMessageId = $rawMessageId ?: 'msg_' . time() . '_' . uniqid();
            $isFromAdmin = $validated['from_admin'] ?? false;


            // Get or create customer
            $customer = Customer::firstOrCreate(
                ['phone' => $phoneNumber],
                ['name' => null, 'phone' => $phoneNumber]
            );

            if ($pushName) {
                $validated['pushname'] = $pushName;
            }

            $allowedMedia = ['image', 'video', 'document', 'audio', 'conversation', 'chat', 'text'];

            // === Ekstrak info media dari raw_message (sebagai fallback) ===
            $mediaUrl = null;
            $fileName = null;
            $mimeType = null;
            $mediaSize = null;

            

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

                    if (!empty($media['caption']) && empty($messageBody)) {
                        $messageBody = $media['caption'];
                    }
                }
            }

            // === MEDIA_ID dari Node.js (jika sudah diunggah ke Laravel) ===
           
            $mediaFileId = $validated['media_id'] ?? $request->input('media_id') ?? null;
            $mediaFilePath = $validated['media_path'] ?? $request->input('media_path') ?? null;

            // Hanya buat message jika ada body atau termasuk tipe media yang diizinkan
            if (!empty($messageBody) || in_array($finalType, $allowedMedia)) {
                $message = Message::create([
                    'customer_id'   => $customer->id,
                    'message_id'    => $finalMessageId,
                    'from'          => $phoneNumber,
                    'to'            => env('WHATSAPP_BUSINESS_PHONE', 'system'),
                    'body'          => $messageBody ?: '[' . strtoupper($validated['type']) . ' Media]',
                    'type'          => $finalType,
                    'status'        => 'pending',
                    'is_incoming'   => !$fromAdmin, // false jika dari admin
                    'parsed'        => false,
                    'chat_status'   => 'active',
                    'media_url'     => $mediaUrl,
                    'media_path'  => $mediaFilePath,
                    'file_name'     => $fileName,
                    // 'mime_type'     => $mimeType,
                    // 'media_size'    => $mediaSize,
                    // 'media_file_id' => $mediaFileId,
                ]);

                // Update media record jika ada media_file_id
                if ($mediaFileId) {
                    Media::where('id', $mediaFileId)->update([
                        'model_type' => Message::class,
                        'model_id'   => $message->id,
                        'message_id' => $message->id, // jika tabel media punya kolom message_id
                    ]);
                }

                Log::channel('whatsapp')->info('Message created', [
                    'message_id' => $message->id,
                    'wa_id'      => $finalMessageId,
                    'from'       => $phoneNumber,
                    'from_admin' => $fromAdmin,
                    'has_media'  => !is_null($mediaFileId),
                ]);

                // Dispatch event HANYA jika BUKAN dari admin
                if (!$fromAdmin) {
                    WhatsAppMessageReceived::dispatch($message, $validated);
                } else {
                    Log::channel('whatsapp')->info('Admin message - skipping bot event');
                }

                return response()->json([
                    'success'     => true,
                    'message_id'  => $message->id,
                    'customer_id' => $customer->id,
                ], 200);
            }

            Log::channel('whatsapp')->debug('Webhook received but no message created', [
                'phone' => $phoneNumber,
                'type'  => $validated['type'],
                'body'  => $messageBody,
            ]);

            return response()->json([
                'success'     => true,
                'message'     => 'Webhook received but no message created',
                'customer_id' => $customer->id,
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::channel('whatsapp')->error('Validation error in webhook', $e->errors());
            return response()->json(['error' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::channel('whatsapp')->error('Error processing webhook', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
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
            'status'    => 'ok',
            'message'   => 'Webhook is working correctly',
            'timestamp' => now(),
        ]);
    }
}