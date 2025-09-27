<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // ensure doctrine/dbal is installed before running this migration
        Schema::table('wallet_transactions', function (Blueprint $table) {
            // change type to varchar(50) and allow null if desired
            $table->string('type', 50)->nullable()->default(null)->change();
        });
    }

    public function down()
    {
        Schema::table('wallet_transactions', function (Blueprint $table) {
            // If previously an ENUM or small varchar you can revert here.
            // Example: revert to varchar(20) - adjust to your original column definition
            $table->string('type', 20)->nullable()->default(null)->change();
        });
    }
};
