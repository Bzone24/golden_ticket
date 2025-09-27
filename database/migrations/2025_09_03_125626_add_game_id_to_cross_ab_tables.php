<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   public function up(): void
{
    // ✅ Add game_id to cross_abcs
    Schema::table('cross_abcs', function (Blueprint $table) {
        $table->foreignId('game_id')
            ->nullable()
            ->after('id')   // changed here
            ->constrained('games');
    });

    // ✅ Add game_id to cross_abc_details
    Schema::table('cross_abc_details', function (Blueprint $table) {
        $table->foreignId('game_id')
            ->nullable()
            ->after('id')   // changed here too
            ->constrained('games');
    });
}

};
