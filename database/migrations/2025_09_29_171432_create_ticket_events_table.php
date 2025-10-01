<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTicketEventsTable extends Migration
{
    public function up()
    {
        Schema::create('ticket_events', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('ticket_id')->constrained('tickets')->onDelete('cascade');
            $table->unsignedBigInteger('user_id')->nullable()->index(); // who performed the action
            $table->enum('event_type', ['EDIT', 'DELETE'])->index();
            $table->json('draw_detail_ids')->nullable(); // array of draw_detail_id's affected
            $table->json('details')->nullable(); // optional: snapshot of removed/changed rows
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('ticket_events');
    }
}
