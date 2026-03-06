<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Message;
use App\Events\WhatsAppMessageReceived;
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
            $phoneNumber = $validated['sender_number'] 
                ?? $validated['from'] 
                ?? $request->input('from')
                ?? $request->input('sender_number');
            
            if (empty($phoneNumber)) {
                Log::channel('whatsapp')->warning('No phone number found in webhook payload', ['payload' => $validated]);
                return response()->json(['error' => 'No phone number found in payload'], 422);
            }
            
            // Clean phone number (remove @ and other characters)
            $phoneNumber = preg_replace('/[^0-9+]/', '', $phoneNumber);

            // Extract message body - try multiple field names
            $messageBody = $validated['body'] 
                ?? $validated['content'] 
                ?? $validated['message'] 
                ?? '';

            // Get or create customer
            $customer = Customer::firstOrCreate(
                ['phone' => $phoneNumber],
                [
                    'name' => null,
                    'phone' => $phoneNumber,
                ]
            );

            // Only create message if type is text or has body content
            if (!empty($messageBody) || in_array($validated['type'], ['image', 'video', 'document', 'audio'])) {
                // Create message directly (no conversation table)
                $message = Message::create([
                    'customer_id' => $customer->id,
                    'message_id' => $validated['message_id'] ?? 'msg_' . time(),
                    'from' => $phoneNumber,
                    'to' => env('WHATSAPP_BUSINESS_PHONE', 'system'),
                    'body' => $messageBody ?: '[' . strtoupper($validated['type']) . ' Media]',
                    'type' => $validated['type'],
                    'status' => 'pending',
                    'is_incoming' => true,
                    'parsed' => false,
                    'chat_status' => 'active',
                ]);

                // Dispatch event
                WhatsAppMessageReceived::dispatch($message, $validated);

                Log::channel('whatsapp')->info('Incoming message', [
                    'message_id' => $message->id,
                    'from' => $phoneNumber,
                    'type' => $validated['type'],
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
