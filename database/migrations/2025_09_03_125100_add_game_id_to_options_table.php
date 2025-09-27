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
    Schema::table('options', function (Blueprint $table) {
        $table->foreignId('game_id')->after('ticket_id')->nullable()->constrained('games');
    });
}

    /**
     * Reverse the migrations.
     */
   public function down()
{
    Schema::table('options', function (Blueprint $table) {
        $table->dropForeign(['game_id']);
        $table->dropColumn('game_id');
    });
}
};
