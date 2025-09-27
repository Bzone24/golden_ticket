<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->json('draw_details_ids');
            $table->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();
            $table->string('number')->nullable();
            $table->enum('option', ['A', 'B', 'C'])->nullable();
            $table->string('qty')->nullable();
            $table->string('total')->nullable();
            $table->enum('status', ['RUNNING', 'COMPLETED', 'LOCK'])->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('options');
    }
};
