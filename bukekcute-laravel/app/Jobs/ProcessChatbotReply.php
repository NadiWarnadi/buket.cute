<?php

namespace App\Jobs;

use App\Models\Message;
use App\Services\ChatbotService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessChatbotReply implements ShouldQueue
{
    use Queueable;

    protected Message $message;

    /**
     * Create a new job instance.
     */
    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    /**
     * Execute the job - process chatbot auto-reply
     */
    public function handle(): void
    {
        try {
            $this->message->load('customer');

            // Process and get auto-reply
            $outgoing = ChatbotService::processMessage($this->message);

            if ($outgoing) {
                Log::info("✅ Auto-reply sent to customer {$this->message->customer_id}: {$outgoing->id}");
            } else {
                Log::debug("No auto-reply needed for message {$this->message->id}");
            }
        } catch (\Exception $e) {
            Log::error("Error processing chatbot reply: {$e->getMessage()}");
            $this->fail($e);
        }
    }
}

