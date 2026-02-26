<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outgoing_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('restrict');
            $table->foreignId('order_id')->nullable()->constrained()->onDelete('set null');
            $table->string('to', 20);
            $table->text('body');
            $table->string('type', 50)->default('text');
            $table->string('status', 50)->default('pending');
            $table->timestamp('sent_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['customer_id', 'status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outgoing_messages');
    }
};
