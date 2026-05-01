<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up()
{
    Schema::table('orders', function (Blueprint $table) {
        $table->string('midtrans_order_id')->nullable()->unique();
        $table->string('midtrans_transaction_status')->nullable();
        $table->string('midtrans_transaction_id')->nullable();
        $table->string('midtrans_payment_type')->nullable();
        $table->string('midtrans_qr_code_url')->nullable();
        $table->text('midtrans_raw_response')->nullable();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Menghapus kembali kolom yang telah ditambahkan
            $table->dropColumn([
                'midtrans_order_id',
                'midtrans_transaction_status',
                'midtrans_transaction_id',
                'midtrans_payment_type',
                'midtrans_qr_code_url',
                'midtrans_raw_response'
            ]);
        });
    }
};