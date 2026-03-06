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
       Schema::create('fuzzy_rules', function (Blueprint $table) {
    $table->id();
    $table->string('intent', 100);
    $table->text('pattern'); // Regex/Keywords
    $table->float('confidence_threshold');
    $table->string('action', 100);
    $table->text('response_template')->nullable();
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fuzzy_rule');
    }
};
