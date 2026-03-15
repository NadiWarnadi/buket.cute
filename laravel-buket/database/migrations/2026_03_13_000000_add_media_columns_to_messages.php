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
        // Add media-related columns to messages table
        if (! Schema::hasColumn('messages', 'media_path')) {
            Schema::table('messages', function (Blueprint $table) {
                $table->string('media_path')->nullable()->after('body');
                $table->string('media_url')->nullable()->after('media_path');
                $table->string('file_name')->nullable()->after('media_url');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            if (Schema::hasColumn('messages', 'media_path')) {
                $table->dropColumn('media_path');
            }
            if (Schema::hasColumn('messages', 'media_url')) {
                $table->dropColumn('media_url');
            }
            if (Schema::hasColumn('messages', 'file_name')) {
                $table->dropColumn('file_name');
            }
        });
    }
};
