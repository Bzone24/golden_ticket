<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ✅ Add game_id to ticket_options
        Schema::table('ticket_options', function (Blueprint $table) {
            $table->foreignId('game_id')
                ->nullable()
                ->after('ticket_id')
                ->constrained('games');
        });

        // ✅ Add game_id to user_draws
        Schema::table('user_draws', function (Blueprint $table) {
            $table->foreignId('game_id')
                ->nullable()
                ->after('draw_detail_id')
                ->constrained('games');
        });
    }

    public function down(): void
    {
        Schema::table('ticket_options', function (Blueprint $table) {
            $table->dropForeign(['game_id']);
            $table->dropColumn('game_id');
        });

        Schema::table('user_draws', function (Blueprint $table) {
            $table->dropForeign(['game_id']);
            $table->dropColumn('game_id');
        });
    }
};
