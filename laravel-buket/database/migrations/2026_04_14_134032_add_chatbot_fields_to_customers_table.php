<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddChatbotFieldsToCustomersTable extends Migration
{
    public function up()
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->integer('retry_count')->default(0)->after('current_context');
            $table->string('last_question')->nullable()->after('retry_count');
            $table->boolean('is_admin_handled')->default(false)->after('last_question');
        });
    }

    public function down()
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['retry_count', 'last_question', 'is_admin_handled']);
        });
    }
}