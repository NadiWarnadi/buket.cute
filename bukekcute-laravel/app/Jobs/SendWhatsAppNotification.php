<?php

namespace App\Jobs;

use App\Models\OutgoingMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendWhatsAppNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $outgoingMessage;
    public $tries = 3;
    public $timeout = 30;

    public function __construct(OutgoingMessage $outgoingMessage)
    {
        $this->outgoingMessage = $outgoingMessage;
    }

    /**
     * Execute the job
     */
    public function handle()
    {
        $msg = $this->outgoingMessage;

        // Skip if already sent
        if ($msg->status !== OutgoingMessage::STATUS_PENDING) {
            return;
        }

        try {
            // Send via WhatsApp API (Node.js)
            $sent = $this->sendViaNodeJS($msg);

            if ($sent) {
                $msg->markAsSent();
                \Log::info("WhatsApp message sent: {$msg->id}");
            } else {
                throw new \Exception('Failed to send message');
            }
        } catch (\Exception $e) {
            \Log::error("SendWhatsAppNotification failed: " . $e->getMessage(), [
                'message_id' => $msg->id,
            ]);
            
            // Mark as failed after retries
            if ($this->attempts() >= $this->tries) {
                $msg->markAsFailed($e->getMessage());
            }

            throw $e;
        }
    }

    /**
     * Send message via Node.js WhatsApp service
     */
    private function sendViaNodeJS(OutgoingMessage $msg)
    {
        try {
            $nodeUrl = config('services.whatsapp.gateway_url');
            $botToken = config('services.whatsapp.bot_token');

            if (!$nodeUrl || !$botToken) {
                Log::error('WhatsApp gateway not configured', [
                    'gateway_url' => $nodeUrl,
                    'bot_token' => $botToken ? 'set' : 'not set',
                ]);
                return false;
            }

            // Prepare payload
            $payload = [
                'to' => $msg->to,
                'message' => $msg->body,
                'type' => $msg->type ?? 'text',
            ];

            // Send POST request to Node.js
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$botToken}",
                'Content-Type' => 'application/json',
            ])
            ->timeout(30)
            ->retry(2, 1000)
            ->post("{$nodeUrl}/api/send-message", $payload);

            if ($response->successful()) {
                Log::info("✅ WhatsApp message sent via Node.js", [
                    'to' => $msg->to,
                    'message_id' => $msg->id,
                    'response' => $response->json(),
                ]);
                return true;
            } else {
                Log::error("❌ WhatsApp Gateway Error", [
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'to' => $msg->to,
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error("❌ Node.js WhatsApp Error: " . $e->getMessage(), [
                'message_id' => $msg->id,
                'to' => $msg->to,
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }
}
