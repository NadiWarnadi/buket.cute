<?php

namespace App\Services\Chatbot;

use App\Models\Customer;
use App\Models\Message;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Log;

class ReplySender
{
    protected WhatsAppService $wa;

    public function __construct(WhatsAppService $wa)
    {
        $this->wa = $wa;
    }

    /**
     * Kirim balasan teks ke customer, dan catat di tabel messages.
     */
    public function send(Customer $customer, string $text, ?int $orderId = null): bool
    {
        try {
            $result = $this->wa->sendText($customer->phone, $text);

            if ($result['success']) {
                Message::create([
                    'customer_id' => $customer->id,
                    'order_id' => $orderId,
                    'message_id' => $result['message_id'] ?? 'reply_' . uniqid(),
                    'from' => config('services.whatsapp.business_phone', 'system'),
                    'to' => $customer->phone,
                    'body' => $text,
                    'type' => 'text',
                    'status' => 'sent',
                    'is_incoming' => false,
                    'parsed' => true,
                    'chat_status' => 'active',
                ]);

                Log::channel('whatsapp')->info('Reply sent', [
                    'customer_id' => $customer->id,
                    'text' => substr($text, 0, 50),
                ]);

                return true;
            }

            Log::channel('whatsapp')->warning('Failed to send reply', [
                'customer_id' => $customer->id,
                'error' => $result['error'] ?? 'Unknown',
            ]);

            return false;
        } catch (\Exception $e) {
            Log::channel('whatsapp')->error('Exception sending reply', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Kirim balasan dan update last_question di ConversationManager.
     */
    public function sendAndRecordQuestion(ConversationManager $conv, string $text, ?int $orderId = null): bool
    {
        $sent = $this->send($conv->getCustomer(), $text, $orderId);
        if ($sent) {
            $conv->setLastQuestion($text);
        }
        return $sent;
    }

    /**
     * Akses customer dari ConversationManager.
     */
    protected function getCustomerFromConv(ConversationManager $conv): Customer
    {
        $reflection = new \ReflectionClass($conv);
        $property = $reflection->getProperty('customer');
        // $property->setAccessible(true);
        return $property->getValue($conv);
    }
}