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
        Schema::table('order_drafts', function (Blueprint $table) {
            // Decimal(5,2) memungkinkan sub-state seperti 1.10, 1.20, dll.
            $table->decimal('custom_sub_state', 5, 2)->nullable()->after('step');
            $table->boolean('is_custom')->default(false)->after('custom_sub_state');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_drafts', function (Blueprint $table) {
            $table->dropColumn(['custom_sub_state', 'is_custom']);
        });
    }
};
