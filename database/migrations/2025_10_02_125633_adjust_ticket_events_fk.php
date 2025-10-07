<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up()
    {
        Schema::table('ticket_events', function (Blueprint $table) {
            $table->dropForeign(['ticket_id']);
        });

        // Recreate FK without cascade delete
        Schema::table('ticket_events', function (Blueprint $table) {
            $table->foreign('ticket_id')
                ->references('id')->on('tickets')
                ->onUpdate('cascade')
                ->onDelete('restrict');
        });
    }

    public function down()
    {
        Schema::table('ticket_events', function (Blueprint $table) {
            $table->dropForeign(['ticket_id']);
            $table->foreign('ticket_id')
                ->references('id')->on('tickets')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });
    }
};