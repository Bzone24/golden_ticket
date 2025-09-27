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
        Schema::create('draw_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('draw_id')->constrained('draws');
            $table->string('claim_a')->nullable();
            $table->string('claim_b')->nullable();
            $table->string('claim_c')->nullable();
            $table->string('claim')->nullable();
            $table->string('ab')->nullable();
            $table->string('ac')->nullable();
            $table->string('bc')->nullable();
            $table->string('claim_ab')->nullable();
            $table->string('claim_ac')->nullable();
            $table->string('claim_bc')->nullable();
            $table->string('total_cross_amt')->nullable();

            $table->string('total_qty')->nullable();
            $table->string('start_time')->nullable();
            $table->string('end_time')->nullable();
            $table->date('date')->nullable();
            $table->unique(['draw_id', 'date']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('draw_details');
    }
};
