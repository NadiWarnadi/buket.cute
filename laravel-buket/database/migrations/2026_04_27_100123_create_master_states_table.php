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
        Schema::create('master_states', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100); // 'greeting', 'input_name', dll
            $table->string('type', 50); // 'greeting','input','fuzzy_inquiry','decision'
            $table->text('prompt_text')->nullable(); // teks yang dikirim ke user saat masuk state
            $table->string('input_key', 100)->nullable(); // key penyimpanan (misal 'name','address')
            $table->longText('validation_rules')->nullable(); // JSON aturan validasi
            $table->string('fuzzy_context', 100)->nullable(); // nama context untuk fuzzy_inquiry
            
            // Foreign key atau reference untuk state selanjutnya
            $table->unsignedBigInteger('next_state_id')->nullable();
            $table->unsignedBigInteger('fallback_state_id')->nullable();
            
            $table->longText('prerequisite_keys')->nullable(); // JSON array
            $table->text('resume_message')->nullable(); // pesan saat resume
            $table->timestamps();

            // Opsional: Jika ingin menambahkan foreign key constraint secara formal
            // $table->foreign('next_state_id')->references('id')->on('master_states')->onDelete('set null');
            // $table->foreign('fallback_state_id')->references('id')->on('master_states')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_states');
    }
};
