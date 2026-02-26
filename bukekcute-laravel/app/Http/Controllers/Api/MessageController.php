<?php

namespace App\Http\Controllers\Api;

use App\Models\Message;
use App\Models\Customer;
use App\Models\OutgoingMessage;
use App\Jobs\ParseWhatsAppMessage;
use App\Jobs\ProcessChatbotReply;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class MessageController extends Controller
{
    /**
     * Store incoming message from WhatsApp bot
     */
    public function store(Request $request)
    {
        // Verify API token
        if ($request->header('X-API-Token') !== config('app.wa_bot_token')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'message_id' => 'required|string|unique:messages,message_id',
            'from' => 'required|string',
            'timestamp' => 'required|date',
            'body' => 'required|string',
            'type' => 'required|string',
            'is_incoming' => 'required|boolean',
            'to' => 'nullable|string',
            'media_id' => 'nullable|string',
            'media_url' => 'nullable|url',
            'media_type' => 'nullable|string|in:image,video,audio,document,sticker',
            'mime_type' => 'nullable|string',
            'media_size' => 'nullable|integer',
            'caption' => 'nullable|string',
        ]);

        try {
            // Node.js sends clean phone number: 6283824665074
            // Don't re-format, use as-is
            $phoneNumber = $validated['from'];

            \Log::info("MessageController store", [
                'received_from' => $validated['from'],
                'phone_number' => $phoneNumber,
                'body' => substr($validated['body'], 0, 50),
            ]);

            // Find or create customer with clean phone number
            $customer = Customer::firstOrCreate(
                ['phone' => $phoneNumber],
                [
                    'name' => 'Customer ' . substr($phoneNumber, -4),
                    'phone' => $phoneNumber,
                ]
            );

            \Log::info("MessageController store - Customer ID: {$customer->id}, Phone: {$customer->phone}");

            // Store message with media fields
            $message = Message::create([
                'customer_id' => $customer->id,
                'message_id' => $validated['message_id'],
                'from' => $validated['from'],
                'to' => $validated['to'] ?? 'bot@whatsapp',
                'body' => $validated['body'],
                'caption' => $validated['caption'] ?? null,
                'type' => $validated['type'],
                'is_incoming' => $validated['is_incoming'],
                'status' => 'delivered',
                'parsed' => false,
                'media_id' => $validated['media_id'] ?? null,
                'media_url' => $validated['media_url'] ?? null,
                'media_type' => $validated['media_type'] ?? null,
                'mime_type' => $validated['mime_type'] ?? null,
                'media_size' => $validated['media_size'] ?? null,
            ]);

            // Queue jobs for processing
            if ($message->is_incoming) {
                // Process for order parsing
                ParseWhatsAppMessage::dispatch($message)->delay(now()->addSeconds(2));
                
                // Process for chatbot auto-reply
                ProcessChatbotReply::dispatch($message)->delay(now()->addSeconds(1));
            }

            return response()->json([
                'success' => true,
                'message' => 'Message saved successfully',
                'data' => [
                    'id' => $message->id,
                    'customer_id' => $customer->id,
                ],
            ], 201);
        } catch (\Exception $e) {
            \Log::error('Error storing message: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to save message',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get unparsed messages for parsing
     */
    public function getUnparsed()
    {
        $messages = Message::unparsed()
            ->with('customer')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $messages,
        ]);
    }

    /**
     * Mark message as parsed
     */
    public function markParsed(Request $request, Message $message)
    {
        $validated = $request->validate([
            'order_id' => 'nullable|exists:orders,id',
        ]);

        $message->markAsParsed($validated['order_id'] ?? null);

        return response()->json([
            'success' => true,
            'message' => 'Message marked as parsed',
        ]);
    }

    /**
     * Mark outgoing message as sent
     */
    public function markSent(Request $request)
    {
        // Verify API token
        if ($request->header('X-API-Token') !== config('app.wa_bot_token')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'message_id' => 'required|string',
            'status' => 'nullable|string|in:sent,delivered,read,failed',
        ]);

        try {
            // Find outgoing message by message_id
            $outgoing = OutgoingMessage::where('body', 'like', '%' . $validated['message_id'] . '%')
                ->orWhere('id', $validated['message_id'])
                ->first();

            if (!$outgoing) {
                // For now, just return success (Node.js doesn't need to block on this)
                return response()->json([
                    'success' => true,
                    'message' => 'Message not found, but status update completed',
                ]);
            }

            $outgoing->update([
                'status' => $validated['status'] ?? 'sent',
            ]);

            \Log::info("✅ Outgoing message {$outgoing->id} marked as {$outgoing->status}");

            return response()->json([
                'success' => true,
                'data' => $outgoing,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error marking message as sent: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to update message status',
            ], 500);
        }
    }

    /**
     * Mark outgoing message as delivered
     */
    public function markDelivered(Request $request)
    {
        // Verify API token
        if ($request->header('X-API-Token') !== config('app.wa_bot_token')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'message_id' => 'required|string',
        ]);

        try {
            $outgoing = OutgoingMessage::where('body', 'like', '%' . $validated['message_id'] . '%')
                ->orWhere('id', $validated['message_id'])
                ->first();

            if ($outgoing) {
                $outgoing->update(['status' => 'delivered']);
            }

            return response()->json([
                'success' => true,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error marking as delivered: ' . $e->getMessage());
            return response()->json(['success' => true]); // Don't fail the bot
        }
    }

    /**
     * Mark outgoing message as read
     */
    public function markRead(Request $request)
    {
        // Verify API token
        if ($request->header('X-API-Token') !== config('app.wa_bot_token')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'message_id' => 'required|string',
        ]);

        try {
            $outgoing = OutgoingMessage::where('body', 'like', '%' . $validated['message_id'] . '%')
                ->orWhere('id', $validated['message_id'])
                ->first();

            if ($outgoing) {
                $outgoing->update(['status' => 'read']);
            }

            return response()->json([
                'success' => true,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error marking as read: ' . $e->getMessage());
            return response()->json(['success' => true]); // Don't fail the bot
        }
    }
}
