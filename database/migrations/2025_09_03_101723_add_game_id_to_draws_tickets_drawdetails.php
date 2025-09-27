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
    Schema::table('draws', function (Blueprint $table) {
        $table->foreignId('game_id')->after('id')->nullable()->constrained('games');
    });

    Schema::table('tickets', function (Blueprint $table) {
        $table->foreignId('game_id')->after('id')->nullable()->constrained('games');
    });

    Schema::table('draw_details', function (Blueprint $table) {
        $table->foreignId('game_id')->after('id')->nullable()->constrained('games');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down()
{
    Schema::table('draws', function (Blueprint $table) {
        $table->dropForeign(['game_id']);
        $table->dropColumn('game_id');
    });

    Schema::table('tickets', function (Blueprint $table) {
        $table->dropForeign(['game_id']);
        $table->dropColumn('game_id');
    });

    Schema::table('draw_details', function (Blueprint $table) {
        $table->dropForeign(['game_id']);
        $table->dropColumn('game_id');
    });
}
};
