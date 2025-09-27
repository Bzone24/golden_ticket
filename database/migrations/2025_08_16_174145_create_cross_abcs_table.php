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
        Schema::create('cross_abcs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();
            $table->json('draw_details_ids')->nullable();
            $table->string('abc')->nullable();
            $table->string('option')->nullable(); // e.g., ABC, AB like this type
            $table->string('number')->nullable();
            $table->string('combination')->nullable();
            $table->string('amt');
            $table->json('ab')->nullable();
            $table->json('ac')->nullable();
            $table->json('bc')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cross_abcs');
    }
};
