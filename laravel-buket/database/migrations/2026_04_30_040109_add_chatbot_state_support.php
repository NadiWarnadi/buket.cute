<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // We only add 'last_activity_at' because the other columns 
        // and the master_states table were already created by previous migrations
        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers', 'last_activity_at')) {
                $table->timestamp('last_activity_at')->nullable()->after('address');
            }
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('last_activity_at');
        });
    }
};
