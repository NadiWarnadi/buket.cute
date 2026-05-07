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
    Schema::table('media', function (Blueprint $table) {
        $table->string('url')->nullable()->after('file_path');
    });
}

public function down()
{
    Schema::table('media', function (Blueprint $table) {
        $table->dropColumn('url');
    });
}
};
