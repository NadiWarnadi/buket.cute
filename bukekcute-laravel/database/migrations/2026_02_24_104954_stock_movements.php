<?php
// database/migrations/2026_01_01_000011_create_stock_movements_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ingredient_id')->constrained()->onDelete('restrict');
            $table->enum('type', ['in', 'out']);
            $table->integer('quantity');
            $table->text('description')->nullable();
            $table->nullableMorphs('reference'); // reference_type, reference_id
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};