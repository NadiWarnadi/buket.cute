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
        Schema::table('customers', function (Blueprint $table) {
            // Menambahkan kolom current_state_id setelah current_context
            $table->unsignedBigInteger('current_state_id')->nullable()->after('current_context');
            
            // Menambahkan index untuk performa
            $table->index('current_state_id', 'customers_current_state_id_foreign');

            // Opsional: Aktifkan jika ingin constraint FK formal ke tabel master_states
            // $table->foreign('current_state_id')->references('id')->on('master_states')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            // Menghapus index dan kolom jika migrasi di-rollback
            $table->dropIndex('customers_current_state_id_foreign');
            $table->dropColumn('current_state_id');
        });
    }
};
