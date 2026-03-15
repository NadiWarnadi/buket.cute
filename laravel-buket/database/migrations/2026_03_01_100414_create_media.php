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
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->morphs('model'); // Menghasilkan model_type & model_id
            $table->string('collection', 100)->nullable();
            $table->string('file_path');
            $table->string('file_name');
            $table->string('mime_type', 100);
            $table->integer('size')->nullable();
            $table->timestamps();
            $table->boolean('is_featured')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
