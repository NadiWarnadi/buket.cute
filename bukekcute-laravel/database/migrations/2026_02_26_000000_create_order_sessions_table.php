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
        Schema::create('order_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            
            // Order data being collected
            $table->string('customer_name')->nullable();
            $table->text('customer_address')->nullable();
            $table->string('product_description')->nullable();
            $table->text('reference_image_url')->nullable();
            $table->enum('delivery_type', ['delivery', 'pickup'])->nullable(); // dikirim atau ambil
            $table->text('greeting_note')->nullable(); // kartu ucapan
            $table->decimal('total_price', 10, 2)->nullable();
            
            // Conversation state tracking
            $table->integer('conversation_step')->default(0); // 0=greeting, 1=waiting_name, 2=waiting_address, etc
            $table->json('conversation_data')->nullable(); // store raw conversation for reference
            
            // Status
            $table->enum('status', ['active', 'confirmed', 'completed', 'cancelled'])->default('active');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_sessions');
    }
};
