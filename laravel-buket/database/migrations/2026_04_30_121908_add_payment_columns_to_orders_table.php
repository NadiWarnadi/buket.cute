<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->enum('payment_method', ['cod', 'transfer', 'qris'])->nullable()->after('status');
            $table->enum('payment_status', ['pending', 'paid', 'confirmed'])->default('pending')->after('payment_method');
            $table->string('payment_proof')->nullable()->after('payment_status');
            $table->timestamp('payment_due_date')->nullable()->after('payment_proof');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'payment_method',
                'payment_status',
                'payment_proof',
                'payment_due_date',
            ]);
        });
    }
};