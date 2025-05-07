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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            //$table->uuid('transaction_id')->unique(); //This value is supplied by the 'transactionable' morph below
            $table->foreignId('institution_group_id')->references('id')->on('institution_groups');
            $table->foreignId('institution_id')->nullable()->references('id')->on('institutions');
            $table->string('wallet')->index()->comment("This can be either CREDIT or DEBT and refers to the institution_group's wallet (Credit_Wallet / Debt_Wallet) being affected by this transaction");
            $table->string('type')->index()->comment('This can be either CREDIT or DEBIT and refers to the nature of the transaction. i.e. It Increases or Decreases the balance of the affected wallet');
            $table->decimal('amount', 15, 2);
            $table->decimal('bbt', 15, 2);
            $table->decimal('bat', 15, 2);
            $table->morphs('transactionable');
            $table->string('reference')->unique();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations. 
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};