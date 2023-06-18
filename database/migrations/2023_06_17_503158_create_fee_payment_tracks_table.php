<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  /**
   * Run the migrations.
   */
  public function up(): void
  {
    Schema::create('fee_payment_tracks', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('fee_payment_id');
      $table->unsignedBigInteger('confirmed_by_user_id');
      $table->float('amount', 10, 2);
      $table->string('method')->nullable();
      $table->string('reference')->unique();
      $table->softDeletes();

      $table->timestamps();

      $table
        ->foreign('fee_payment_id')
        ->references('id')
        ->on('fee_payments')
        ->cascadeOnDelete();
      $table
        ->foreign('confirmed_by_user_id')
        ->references('id')
        ->on('users')
        ->cascadeOnDelete();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('fee_payment_tracks');
  }
};
