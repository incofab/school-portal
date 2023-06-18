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
    Schema::create('fee_payments', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('institution_id');
      $table->unsignedBigInteger('fee_id');
      $table->unsignedBigInteger('user_id');
      $table->float('fee_amount', 10, 2);
      $table->float('amount_paid', 10, 2);
      $table->float('amount_remaining', 10, 2)->default(0);
      $table->unsignedBigInteger('academic_session_id')->nullable();
      $table->string('term')->nullable();
      $table->softDeletes();

      $table->timestamps();

      $table
        ->foreign('institution_id')
        ->references('id')
        ->on('institutions')
        ->cascadeOnDelete();
      $table
        ->foreign('fee_id')
        ->references('id')
        ->on('fees')
        ->cascadeOnDelete();
      $table
        ->foreign('user_id')
        ->references('id')
        ->on('users')
        ->cascadeOnDelete();
      $table
        ->foreign('academic_session_id')
        ->references('id')
        ->on('academic_sessions')
        ->cascadeOnDelete();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('fee_payments');
  }
};
