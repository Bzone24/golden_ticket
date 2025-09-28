<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
       Schema::table('users', function (Blueprint $table) {
    if (!Schema::hasColumn('users', 'full_name')) {
        $table->string('full_name')->nullable()->after('last_name');
    }
    if (!Schema::hasColumn('users', 'username')) {
        $table->string('username', 100)->nullable()->after('full_name');
    }
    if (!Schema::hasColumn('users', 'login_id')) {
        $table->string('login_id', 100)->nullable()->after('username');
    }

    if (Schema::hasColumn('users', 'email')) {
        $table->string('email')->nullable()->change();
    }
    if (Schema::hasColumn('users', 'mobile_number')) {
        $table->string('mobile_number')->nullable()->change();
    }
});


        // Backfill full_name
     // Reset counter variable
DB::statement("SET @n := 100");

// Sequentially assign login_id starting at ABC101
DB::statement("
    UPDATE users u
    JOIN (
        SELECT id, (@n := @n + 1) AS seq
        FROM (SELECT id FROM users ORDER BY id) x
    ) t ON u.id = t.id
    SET u.login_id = CONCAT('ABC', t.seq)
    WHERE u.login_id IS NULL
");

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['full_name', 'username', 'login_id']);
            // Note: email/mobile revert not included (depends on original)
        });
    }
};
