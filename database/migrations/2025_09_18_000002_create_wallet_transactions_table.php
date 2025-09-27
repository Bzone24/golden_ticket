<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
class CreateWalletTransactionsTable extends Migration
{
    public function up()
    {
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('wallet_id');
            $table->enum('type', ['credit','debit','transfer_in','transfer_out','admin_credit','admin_debit']);
            $table->decimal('amount', 20, 2);
            $table->decimal('balance', 20, 2)->comment('wallet balance after this tx');
            $table->unsignedBigInteger('performed_by')->nullable()->comment('user/admin who caused tx');
            $table->unsignedBigInteger('related_id')->nullable()->comment('ticket id, claim id or transfer id');
            $table->string('note')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->foreign('wallet_id')->references('id')->on('wallets')->onDelete('cascade');
        });
    }
    public function down()
    {
        Schema::dropIfExists('wallet_transactions');
    }
}
