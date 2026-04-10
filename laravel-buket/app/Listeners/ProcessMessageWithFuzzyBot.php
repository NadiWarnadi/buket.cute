<?php

namespace App\Listeners;

use App\Events\WhatsAppMessageReceived;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Message;
use App\Services\FuzzyBotService;
use App\Services\OrderDraftService;
use App\Services\ParameterValidationService;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessMessageWithFuzzyBot
{
    protected FuzzyBotService $fuzzyBotService;

    protected WhatsAppService $whatsappService;

    protected OrderDraftService $orderDraftService;

    public function __construct(
        FuzzyBotService $fuzzyBotService,
        WhatsAppService $whatsappService,
        OrderDraftService $orderDraftService
    ) {
        $this->fuzzyBotService = $fuzzyBotService;
        $this->whatsappService = $whatsappService;
        $this->orderDraftService = $orderDraftService;
    }

    /**
     * Handle the event.
     * IMPORTANT: Uses eager loading dengan with() untuk menghindari N+1 queries
     */
    public function handle(WhatsAppMessageReceived $event): void
    {
        $message = $event->message;
        $traceId = uniqid('trace_', true);

        if ($message->from === env('WHATSAPP_BUSINESS_PHONE', 'system')) {
            return;
        }

        $processed = DB::transaction(function () use ($message) {
            $fresh = Message::where('id', $message->id)->lockForUpdate()->first();
            if (!$fresh || $fresh->parsed) {
                return false;
            }

            $fresh->update([
                'parsed' => true,
                'parsed_at' => now(),
            ]);

            return true;
        });

        if (!$processed) {
            Log::channel('whatsapp')->warning('Duplicate listener invocation skipped', [
                'message_id' => $message->id,
                'wa_message_id' => $message->message_id,
                'trace_id' => $traceId,
            ]);
            return;
        }

        Log::channel('whatsapp')->info('Listener Berjalan - Tipe: ' . $message->type, ['trace_id' => $traceId]);

        try {
            // Only process text messages
            // Izinkan tipe 'text' atau 'conversation'
            // Izinkan 'text' ATAU 'conversation' agar Bot mau jalan
            if (!in_array($message->type, ['text', 'conversation','chat']) || empty($message->body)) {
                return;
            }

            // Eager load relationships untuk menghindari N+1
            $message->load(['customer', 'order']);
            // Get atau create customer
            $customer = $message->customer ?? Customer::getOrCreateFromPhone($message->from);

            // Update message dengan customer
            if (!$message->customer_id) {
                $message->update([
                    'customer_id' => $customer->id,
                ]);
            }

            $userBody = trim($message->body);

            // PRIORITY 1: Check for greeting - always process as new conversation
            // Need to check active draft first for greeting priority logic
            $activeDraft = $this->orderDraftService->getCustomerActiveDraft($customer);

            if ($this->isGreeting($userBody)) {
                Log::channel('whatsapp')->info('Greeting detected - resetting context', [
                    'message_id' => $message->id,
                    'body' => $userBody,
                ]);

                // Reset customer context for new conversation
                $customer->update(['current_context' => null]);

                // Cancel any active draft if greeting detected
                if ($activeDraft) {
                    $activeDraft->delete();
                    Log::channel('whatsapp')->info('Active draft cancelled due to greeting', [
                        'draft_id' => $activeDraft->id,
                        'customer_id' => $customer->id,
                    ]);
                }

                $result = $this->fuzzyBotService->processMessageWithContext($userBody, null);
            }
            // PRIORITY 2: Check jika ada active order draft (collecting parameters)
            elseif ($activeDraft) {
                $currentStep = $activeDraft->step;
                if ($currentStep === 'confirming') {
                    $result = $this->fuzzyBotService->processOrderConfirmation($userBody, $customer);
                } else {
                    $result = $this->fuzzyBotService->processOrderCollection($userBody, $customer);
                }
            }
            // PRIORITY 3: General processing
            else {
                if ($this->isOrderIntent($userBody)) {
                    $result = $this->fuzzyBotService->processOrderCollection($userBody, $customer);
                } else {
                    $result = $this->fuzzyBotService->processMessageWithContext(
                        $userBody,
                        $customer->current_context ?? null
                    );
                }
            }

            // Update message dengan parsing result
            $message->update([
                'parsed' => true,
                'parsed_at' => now(),
                'chat_status' => 'active',
                // 'chat_status' => ($result['matched'] ?? false) ? 'active' : 'pending',
            ]);

            // Send automatic response
            if (($result['matched'] ?? false) && !empty($result['response'])) {
                if ($this->shouldSendAutoReply($result['action'] ?? null)) {
                    $this->sendAutoReply($message, $result, $traceId);
                }

                Log::channel('whatsapp')->info('Message processed successfully', [
                    'message_id' => $message->id,
                    'intent' => $result['intent'] ?? null,
                    'action' => $result['action'] ?? null,
                    'customer_id' => $customer->id,
                    'trace_id' => $traceId,
                ]);
            } else {
                $fallbackText = "Maaf ka, saya belum paham maksudnya. Bisa ketik 'Bantuan' atau 'Pesan'?";

                 $this->whatsappService->sendText($message->from, $fallbackText);
                    Message::create([
                                'customer_id' => $message->customer_id,
                                'message_id'  => 'fallback_' . time() . '_' . uniqid(),
                                'from'        => env('WHATSAPP_BUSINESS_PHONE', 'system'),
                                'to'          => $message->from,
                                'body'        => $fallbackText,
                                'type'        => 'text',
                                'status'      => 'sent',
                                'is_incoming' => false,
                                'parsed'      => true,
                                'chat_status' => 'active',
                            ]);
                            
                Log::channel('whatsapp')->debug('No match found for message', [
                    'message_id' => $message->id,
                    'body' => $message->body,
                    'customer_id' => $customer->id,
                ]);
            }
        } catch (\Exception $e) {
            Log::channel('whatsapp')->error('Error processing message', [
                'message_id' => $message->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Detect if message contains greeting intent
     * Keywords: halo, hai, hello, salam, dll
     */
    private function isGreeting(string $message): bool
    {
        $greetingKeywords = [
            'halo', 'hai', 'hay', 'hello', 'hey', 'hi',
            'assalamualaikum', 'asalamualaikum', 'salam',
            'pagi', 'siang', 'sore', 'malam',
            'punten', 'spada', 'selamat'
        ];

        $lowerMessage = strtolower($message);

        foreach ($greetingKeywords as $keyword) {
            if (strpos($lowerMessage, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Detect if message contains order intent
     * Keywords: pesan, order, beli, ingin, mau, ambil, dst
     */
    private function isOrderIntent(string $message): bool
    {
        $orderKeywords = ['pesan', 'order', 'beli', 'ingin', 'mau', 'ambil', 'sewa', 'kasih', 'bikin', 'buatkan'];
        $lowerMessage = strtolower($message);

        foreach ($orderKeywords as $keyword) {
            if (strpos($lowerMessage, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if we should send automatic reply
     */
    private function shouldSendAutoReply(?string $action): bool
    {
        if (empty($action)) {
            return true; // Default: send reply
        }

        // Don't auto-reply for certain actions
        $noAutoReplyActions = ['escalate', 'manual_review', 'pending'];

        return ! in_array($action, $noAutoReplyActions);
    }

    /**
     * Send automatic reply through WhatsApp
     */
    private function sendAutoReply(Message $message, array $result, string $traceId = ''): void
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
                    'message_id' => $replyResult['message_id'] ?? 'msg_'.time() . '_' . uniqid(),
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
                    'trace_id' => $traceId,
                ]);
            }
        } catch (\Exception $e) {
            Log::channel('whatsapp')->error('Failed to send automatic reply', [
                'message_id' => $message->id,
                'error' => $e->getMessage(),
                'trace_id' => $traceId,
            ]);
        }
    }
}
