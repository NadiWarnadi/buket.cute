<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WhatsAppMessageReceived
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Message $message;
    public array $payload;

    public function __construct(Message $message, array $payload = [])
    {
        $this->message = $message;
        $this->payload = $payload;
    }
}