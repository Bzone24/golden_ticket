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
    Schema::create('games', function (Blueprint $table) {
        $table->id();
        $table->string('name');   // Example: N1, N2, future N3
        $table->string('slug')->unique(); // lowercase identifier
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
   public function down()
{
    Schema::dropIfExists('games');
}
};
