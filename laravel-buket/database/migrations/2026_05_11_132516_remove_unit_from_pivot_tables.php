<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_ingredient', function (Blueprint $table) {
            $table->dropColumn('unit');
        });

        Schema::table('order_item_ingredients', function (Blueprint $table) {
            $table->dropColumn('unit');
        });
    }

    public function down(): void
    {
        Schema::table('product_ingredient', function (Blueprint $table) {
            $table->string('unit', 50)->after('quantity');
        });

        Schema::table('order_item_ingredients', function (Blueprint $table) {
            $table->string('unit', 50)->after('quantity');
        });
    }
};