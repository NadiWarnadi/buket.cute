<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('fuzzy_rules', function (Blueprint $table) {
            $table->id();
            $table->string('intent', 100)->unique();
            $table->text('pattern'); // Keywords/Patterns separated by comma
            $table->float('confidence_threshold')->default(0.6); // 60% similarity minimum
            $table->string('action', 100); // e.g., 'reply', 'escalate', 'order', etc
            $table->text('response_template')->nullable(); // Response message template
            $table->string('context_slug')->nullable(); // Current conversation stage
            $table->string('next_context')->nullable(); // Next stage to move to
            $table->integer('priority')->default(0); // Higher = more priority
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Indexes for fast searching
            $table->index('context_slug');
            $table->index('is_active');
            $table->index('action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fuzzy_rules');
    }
};
