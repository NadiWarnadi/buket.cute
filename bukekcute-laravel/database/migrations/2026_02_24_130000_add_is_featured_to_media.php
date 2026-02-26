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
        Schema::table('media', function (Blueprint $table) {
            if (!Schema::hasColumn('media', 'is_featured')) {
                $table->boolean('is_featured')->default(false)->after('size');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('media', function (Blueprint $table) {
            if (Schema::hasColumn('media', 'is_featured')) {
                $table->dropColumn('is_featured');
            }
        });
    }
};
