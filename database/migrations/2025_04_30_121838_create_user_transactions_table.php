<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('user_transactions', function (Blueprint $table) {
      $table->id();
      $table
        ->string('type')
        ->index()
        ->comment(
          'This can be either CREDIT or DEBIT and refers to the nature of the transaction. i.e. It Increases or Decreases the balance of the affected wallet'
        );
      $table->decimal('amount', 15, 2);
      $table->decimal('bbt', 15, 2);
      $table->decimal('bat', 15, 2);
      $table->morphs('entity'); //'The entity initiating the transaction. i.e Partner, User, etc'
      $table->morphs('transactionable'); //The nature of the transaction. i.e Funding, Withdrawal
      $table->string('reference')->index();
      $table->text('remark')->nullable();
      $table->json('meta')->nullable();
      $table->timestamps();
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('user_transactions');
  }
};
