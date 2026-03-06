<?php

namespace App\Http\Controllers\Api;

use App\Models\Message;
use App\Models\Customer;
use App\Models\OutgoingMessage;
use App\Conversations\BuketCuteConversation;
use BotMan\BotMan\BotMan;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class BotManController extends Controller
{
    /**
     * Handle incoming message from WhatsApp and process with BotMan
     */
    public function handleWhatsApp(Request $request)
    {
        // Verify API token
        if ($request->header('X-API-Token') !== config('app.wa_bot_token')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'message_id' => 'required|string|unique:messages,message_id',
            'from' => 'required|string',
            'timestamp' => 'required|numeric',
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
            $phoneNumber = $validated['from'];

            // Check for duplicate
            $existingMessage = Message::where('message_id', $validated['message_id'])->first();
            if ($existingMessage) {
                \Log::warn("⚠️ Duplicate message_id: {$validated['message_id']}");
                return response()->json([
                    'success' => true,
                    'message' => 'Message already exists',
                    'data' => [
                        'id' => $existingMessage->id,
                        'customer_id' => $existingMessage->customer_id,
                    ],
                ], 200);
            }

            // Get or create customer
            $customer = Customer::firstOrCreate(
                ['phone' => $phoneNumber],
                [
                    'name' => 'Customer ' . substr($phoneNumber, -4),
                    'phone' => $phoneNumber,
                ]
            );

            // Parse timestamp
            $createdAtTime = null;
            if (!empty($validated['timestamp'])) {
                try {
                    $createdAtTime = \Carbon\Carbon::createFromTimestamp($validated['timestamp']);
                } catch (\Exception $e) {
                    \Log::warn("Could not parse timestamp: " . $e->getMessage());
                }
            }

            // Store incoming message
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
                'parsed' => true, // Mark as processed by BotMan
                'media_id' => $validated['media_id'] ?? null,
                'media_url' => $validated['media_url'] ?? null,
                'media_type' => $validated['media_type'] ?? null,
                'mime_type' => $validated['mime_type'] ?? null,
                'media_size' => $validated['media_size'] ?? null,
                'created_at' => $createdAtTime,
            ]);

            \Log::info("✅ Message stored: {$message->id} from customer {$customer->id}");

            // Process with BotMan if incoming
            if ($message->is_incoming) {
                $this->processBotMan($customer, $message);
            }

            return response()->json([
                'success' => true,
                'message' => 'Message processed',
                'data' => [
                    'id' => $message->id,
                    'customer_id' => $customer->id,
                ],
            ], 201);

        } catch (\Exception $e) {
            \Log::error('Error in BotManController: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'error' => 'Failed to process message',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Process message with BotMan Conversation
     */
    private function processBotMan(Customer $customer, Message $message)
    {
        try {
            // Create BotMan instance
            $config = [
                'conversation_cache_time' => 60,
            ];
            
            $botman = resolve('botman');

            // Set customer info
            $botman->setUser($customer->id, [
                'phone' => $customer->phone,
                'name' => $customer->name,
            ]);

            // Start conversation if not already in one
            if (!$botman->isUserInConversation($customer->id)) {
                $botman->startConversation(new BuketCuteConversation());
            } else {
                // Continue existing conversation
                $botman->processMessage(new BuketCuteConversation());
            }

            // Send message through BotMan
            $botman->say($message->body, $customer->phone);

        } catch (\Exception $e) {
            \Log::error("BotMan conversation error: " . $e->getMessage());
        }
    }
}
