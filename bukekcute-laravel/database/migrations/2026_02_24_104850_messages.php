<?php
// database/migrations/2026_01_01_000009_create_messages_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('restrict');
            $table->foreignId('order_id')->nullable()->constrained()->onDelete('set null');
            $table->string('message_id', 100)->nullable()->unique();
            $table->string('from', 20);
            $table->string('to', 20);
            $table->text('body');
            $table->string('type', 50);
            $table->string('status', 50)->nullable();
            $table->boolean('is_incoming');
            $table->boolean('parsed')->default(false);
            $table->timestamp('parsed_at')->nullable();
            $table->timestamps();

            $table->index(['customer_id', 'order_id', 'parsed', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};