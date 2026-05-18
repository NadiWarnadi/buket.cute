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
    public function handleWhatsAppMessage(Request $request)
    {
        $apiKey = $request->header('x-api-key');
        if ($apiKey !== env('WHATSAPP_WEBHOOK_KEY')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        try {
            $validated = $request->validate([
                'type'          => 'required|string',
                'from'          => 'nullable|string',
                'sender_number' => 'nullable|string',
                'body'          => 'nullable|string',
                'content'       => 'nullable|string',
                'message'       => 'nullable|string',
                'caption'       => 'nullable|string',
                'isGroup'       => 'nullable|boolean',
                'timestamp'     => 'nullable|integer',
                'message_id'    => 'nullable|string',
                'media_id'      => 'nullable|integer',
                'media_path'    => 'nullable|string',
                'pushname' => 'nullable|string',
                'raw_message'   => 'nullable|array',
            ]);

            $phoneNumber = preg_replace('/[^0-9+]/', '',
                $validated['sender_number']
                ?? $validated['from']
                ?? $request->input('from') ?? '');

            $messageBody = $validated['body']
                ?? $validated['caption']
                ?? $validated['content']
                ?? $validated['message']
                ?? '';

            $finalType = $validated['type'];
            $pushName = $validated['pushname'] ?? $request->input('pushname') ?? null;
            $fromAdmin = $validated['from_admin'] ?? $request->input('from_admin') ?? false;

            if (in_array($finalType, ['protocol', 'sender_data', 'read_receipt'])) {
                return response()->json(['success' => true, 'message' => 'Ignored'], 200);
            }

            $rawMessageId = $validated['message_id'] ?? $request->input('message_id');
            if ($rawMessageId && Message::where('message_id', $rawMessageId)->exists()) {
                return response()->json(['success' => true, 'message' => 'Duplicate ignored'], 200);
            }

            $finalMessageId = $rawMessageId ?: 'msg_' . time() . '_' . uniqid();

            $customer = Customer::firstOrCreate(
                ['phone' => $phoneNumber],
                ['phone' => $phoneNumber]
            );
           

            if (empty($messageBody) && !in_array($finalType, ['image', 'video', 'document', 'audio'])) {
                return response()->json(['success' => true, 'message' => 'No content'], 200);
            }

            $message = Message::create([
                'customer_id' => $customer->id,
                'message_id'  => $finalMessageId,
                'from'        => $phoneNumber,
                'to'          => env('WHATSAPP_BUSINESS_PHONE', 'system'),
                'body'        => $messageBody ?: '[' . strtoupper($finalType) . ' Media]',
                'type'        => $finalType,
                'status'      => 'pending',
                'is_incoming' => !$fromAdmin,
                'parsed'      => false,
                'chat_status' => 'active',
            ]);

            // Hubungkan media jika Node.js mengirim media_id & media_path
            $mediaId = $validated['media_id'] ?? $request->input('media_id');
            if ($mediaId) {
                $mediaRecord = Media::find($mediaId);
                if ($mediaRecord) {
                    // Pastikan file_path sudah terisi (dari upload endpoint)
                    $mediaRecord->update([
                        'file_path' => $validated['media_path'] ?? $mediaRecord->file_path,
                    ]);
                    // Hubungkan ke message secara polymorphic
                    $message->media()->save($mediaRecord);
                }
            }

            if (!$fromAdmin) {
                WhatsAppMessageReceived::dispatch($message, $validated);
            }

            return response()->json([
                'success'     => true,
                'message_id'  => $message->id,
                'customer_id' => $customer->id,
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::channel('whatsapp')->error('Validation error', $e->errors());
            return response()->json(['error' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::channel('whatsapp')->error('Error processing webhook', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => 'Internal server error'], 500);
        }
    }


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