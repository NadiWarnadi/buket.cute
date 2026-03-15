<?php

namespace App\Console\Commands;

use App\Services\FuzzyBotService;
use Illuminate\Console\Command;

class TestFuzzyBot extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fuzzy:test {message : The message to test}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test fuzzy bot with a message';

    /**
     * Execute the console command.
     */
    public function handle(FuzzyBotService $fuzzyBot): int
    {
        $message = $this->argument('message');

        $this->info('Testing message: '.$message);
        $this->newLine();

        $result = $fuzzyBot->processMessage($message);

        if ($result['matched']) {
            $this->info('✓ Match found!');
            $this->line('Intent: '.$result['intent']);
            $this->line('Action: '.$result['action']);
            $this->line('Confidence: '.$result['confidence'] * 100 .'%');
            $this->line('Response: '.$result['response']);
        } else {
            $this->warn('✗ No match found');
        }

        return Command::SUCCESS;
    }
}
