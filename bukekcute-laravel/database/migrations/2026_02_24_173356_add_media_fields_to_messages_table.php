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
        Schema::table('messages', function (Blueprint $table) {
            $table->string('media_id', 255)->nullable()->after('type')->comment('Media ID dari WhatsApp/source');
            $table->string('media_url', 500)->nullable()->after('media_id')->comment('URL untuk download media');
            $table->string('media_type', 50)->nullable()->after('media_url')->comment('image, video, audio, document, sticker');
            $table->string('mime_type', 100)->nullable()->after('media_type')->comment('MIME type: image/jpeg, video/mp4, etc');
            $table->integer('media_size')->nullable()->after('mime_type')->comment('Ukuran file dalam bytes');
            $table->string('caption', 500)->nullable()->after('body')->comment('Caption untuk media');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn(['media_id', 'media_url', 'media_type', 'mime_type', 'media_size', 'caption']);
        });
    }
};
