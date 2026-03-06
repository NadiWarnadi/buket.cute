<?php

namespace App\Listeners;

use App\Events\WhatsAppMessageReceived;
use App\Models\Message;
use Illuminate\Support\Facades\Log;

class ParseWhatsAppMessage
{
    /**
     * Handle the event.
     */
    public function handle(WhatsAppMessageReceived $event): void
    {
        try {
            $message = $event->message;

            // Lakukan parsing/processing sesuai kebutuhan
            // Contoh: cek apakah pesan berisi keyword tertentu, trigger automation, dll.

            Log::channel('whatsapp')->info('Message parsed', [
                'message_id' => $message->id,
                'from' => $message->from,
                'type' => $message->type,
                'content' => substr($message->body, 0, 50)
            ]);

            // Update message status parsed
            $message->update([
                'parsed' => true,
                'parsed_at' => now()
            ]);

        } catch (\Exception $e) {
            Log::channel('whatsapp')->error('Error in ParseWhatsAppMessage listener', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
