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
    public array $rawData;

    public function __construct(Message $message, array $rawData = [])
    {
        $this->message = $message;
        $this->rawData = $rawData;
    }
}
