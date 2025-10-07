<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('ticket_options', function (Blueprint $table) {
            $table->boolean('voided')->default(false)->after('updated_at')->index();
        });

        Schema::table('cross_abc_details', function (Blueprint $table) {
            $table->boolean('voided')->default(false)->after('updated_at')->index();
        });
    }

    public function down()
    {
        Schema::table('ticket_options', function (Blueprint $table) {
            $table->dropColumn('voided');
        });

        Schema::table('cross_abc_details', function (Blueprint $table) {
            $table->dropColumn('voided');
        });
    }
};
