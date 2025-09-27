<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   public function up()
    {
        Schema::table('tickets', function (Blueprint $table) {
            // nullable to avoid breaking existing inserts
            $table->unsignedBigInteger('draw_detail_id')->nullable()->after('game_id');
            $table->index('draw_detail_id');
        });
    }

     public function down()
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropIndex(['draw_detail_id']);
            $table->dropColumn('draw_detail_id');
        });
    }
};
