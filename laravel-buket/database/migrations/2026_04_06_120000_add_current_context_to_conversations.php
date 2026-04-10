<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add current_context column to customers table (since conversations table was dropped)
     * This will track conversation context per customer instead of per conversation
     */
    public function up(): void
    {
        if (Schema::hasTable('customers') && !Schema::hasColumn('customers', 'current_context')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->string('current_context')->nullable()->after('address');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('current_context');
        });
    }
};
