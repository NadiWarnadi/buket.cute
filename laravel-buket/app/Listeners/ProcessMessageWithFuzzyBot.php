<?php

namespace App\Listeners;

use App\Events\WhatsAppMessageReceived;
use App\Models\Message;
use App\Services\FuzzyBotService;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Log;

class ProcessMessageWithFuzzyBot
{
    protected FuzzyBotService $fuzzyBotService;
    protected WhatsAppService $whatsappService;

    public function __construct(
        FuzzyBotService $fuzzyBotService,
        WhatsAppService $whatsappService
    ) {
        $this->fuzzyBotService = $fuzzyBotService;
        $this->whatsappService = $whatsappService;
    }

    /**
     * Handle the event.
     */
    public function handle(WhatsAppMessageReceived $event): void
    {
        $message = $event->message;
        // $payload = $event->payload;

        try {
            // Only process text messages
            if ($message->type !== 'text' || empty($message->body)) {
                return;
            }

            // Process message with fuzzy bot
            $result = $this->fuzzyBotService->processMessage($message->body);

            // Update message with parsing result
            $message->update([
                'parsed' => true,
                'parsed_at' => now(),
                'chat_status' => $result['matched'] ? 'processed' : 'pending',
            ]);

            // If matched, prepare and send automatic response
            if ($result['matched'] && !empty($result['response'])) {
                // Check if we should send automatic reply based on action
                if ($this->shouldSendAutoReply($result['action'])) {
                    $this->sendAutoReply($message, $result);
                }

                Log::channel('whatsapp')->info('Message processed by fuzzy bot', [
                    'message_id' => $message->id,
                    'intent' => $result['intent'],
                    'confidence' => $result['confidence'],
                    'action' => $result['action'],
                ]);
            } else {
                Log::channel('whatsapp')->debug('No fuzzy match found for message', [
                    'message_id' => $message->id,
                    'body' => $message->body,
                ]);
            }
        } catch (\Exception $e) {
            Log::channel('whatsapp')->error('Error processing message with fuzzy bot', [
                'message_id' => $message->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Determine if we should send automatic reply
     */
    private function shouldSendAutoReply(string $action): bool
    {
        // Don't auto-reply for certain actions
        $noAutoReplyActions = ['escalate', 'manual_review', 'pending'];
        
        return !in_array($action, $noAutoReplyActions);
    }

    /**
     * Send automatic reply through WhatsApp
     */
    private function sendAutoReply(Message $message, array $result): void
    {
        try {
            // Send reply via WhatsApp service
            $replyResult = $this->whatsappService->sendText(
                $message->from,
                $result['response']
            );

            if ($replyResult['success']) {
                // Create reply message record
                Message::create([
                    'customer_id' => $message->customer_id,
                    'order_id' => $message->order_id,
                    'message_id' => $replyResult['message_id'] ?? 'msg_' . time(),
                    'from' => env('WHATSAPP_BUSINESS_PHONE', 'system'),
                    'to' => $message->from,
                    'body' => $result['response'],
                    'type' => 'text',
                    'status' => 'sent',
                    'is_incoming' => false,
                    'parsed' => true,
                    'parsed_at' => now(),
                    'chat_status' => 'active',
                ]);

                Log::channel('whatsapp')->info('Automatic reply sent', [
                    'original_message_id' => $message->id,
                    'reply_message_id' => $replyResult['message_id'] ?? null,
                    'intent' => $result['intent'],
                ]);
            }
        } catch (\Exception $e) {
            Log::channel('whatsapp')->error('Failed to send automatic reply', [
                'message_id' => $message->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
