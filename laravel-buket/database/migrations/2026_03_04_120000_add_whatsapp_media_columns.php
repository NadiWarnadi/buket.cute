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
        // Tambahkan kolom WhatsApp ke tabel media jika belum ada
        if (!Schema::hasColumn('media', 'message_id')) {
            Schema::table('media', function (Blueprint $table) {
                $table->foreignId('message_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
                $table->string('file_type', 50)->nullable()->after('mime_type'); // image, video, audio, document
                $table->string('file_size', 50)->nullable()->after('size'); // human readable size
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('media', function (Blueprint $table) {
            if (Schema::hasColumn('media', 'message_id')) {
                $table->dropForeignKeyIfExists(['message_id']);
                $table->dropColumn('message_id');
            }
            if (Schema::hasColumn('media', 'file_type')) {
                $table->dropColumn('file_type');
            }
            if (Schema::hasColumn('media', 'file_size')) {
                $table->dropColumn('file_size');
            }
        });
    }
};
