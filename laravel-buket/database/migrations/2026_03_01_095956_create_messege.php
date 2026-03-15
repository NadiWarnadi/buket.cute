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

        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained();
            $table->foreignId('order_id')->nullable()->constrained();
            $table->string('message_id', 100); // WhatsApp ID
            $table->string('from', 20);
            $table->string('to', 20);
            $table->text('body');
            $table->string('type', 50); // text, image, etc
            $table->string('status', 50)->nullable(); // sent, read
            $table->boolean('is_incoming');
            $table->boolean('parsed')->default(false);
            $table->timestamp('parsed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
