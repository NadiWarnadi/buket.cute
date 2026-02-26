<?php

namespace App\Console\Commands;

use App\Models\Message;
use App\Jobs\ParseWhatsAppMessage;
use Illuminate\Console\Command;

class ParsePendingMessages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'whatsapp:parse-messages {--limit=10 : Number of messages to process}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Parse pending WhatsApp messages and create orders';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $limit = $this->option('limit');

        $messages = Message::unparsed()
            ->limit($limit)
            ->get();

        if ($messages->isEmpty()) {
            $this->info('No pending messages to parse');
            return Command::SUCCESS;
        }

        $this->info("Processing {$messages->count()} messages...");

        foreach ($messages as $message) {
            try {
                // Queue the parsing job
                ParseWhatsAppMessage::dispatch($message);
                $this->line("✓ Queued message: {$message->id}");
            } catch (\Exception $e) {
                $this->error("✗ Error: {$e->getMessage()}");
            }
        }

        $this->info("Done! Messages queued for processing");
        return Command::SUCCESS;
    }
}
