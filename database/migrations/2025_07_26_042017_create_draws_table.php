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
        Schema::create('draws', function (Blueprint $table) {
            $table->id();
            $table->decimal('price', 8);
            $table->string('start_time');
            $table->string('end_time');
            $table->enum('status', ['PENDING', 'ACTIVE', 'RUNNING', 'INACTIVE'])->nullable()->default(null);
            $table->string('total_collection')->nullable();
            $table->string('result')->nullable();
            $table->string('total_rewards')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('draws');
    }
};
