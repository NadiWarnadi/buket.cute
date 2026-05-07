<?php

namespace App\Console\Commands;

use App\Models\Message;
use App\Models\Media;
use Illuminate\Console\Command;

class SyncMessageMedia extends Command
{
    protected $signature = 'sync:message-media';
    protected $description = 'Migrate redundant media columns from messages to media table';

    public function handle()
    {
        $messages = Message::whereNotNull('media_path')
            ->orWhereNotNull('media_url')
            ->get();

        foreach ($messages as $msg) {
            // Cek apakah media untuk message ini sudah ada
            $existing = Media::where('message_id', $msg->id)->first();

            if (!$existing) {
                Media::create([
                    'message_id' => $msg->id,
                    'file_path'  => $msg->media_path ?? '',
                    'file_name'  => $msg->file_name ?? '',
                    'url'        => $msg->media_url ?? null,
                    'mime_type'  => 'unknown', // bisa dikosongkan
                    'size'       => 0,
                    // model_type & model_id biarkan NULL agar khusus WhatsApp
                ]);
            } else {
                // Update jika perlu (misalnya masih ada perbedaan)
                $existing->update([
                    'file_path' => $msg->media_path ?? $existing->file_path,
                    'file_name' => $msg->file_name ?? $existing->file_name,
                    'url'       => $msg->media_url ?? $existing->url,
                ]);
            }
        }

        $this->info('Sinkronisasi selesai.');
    }
}