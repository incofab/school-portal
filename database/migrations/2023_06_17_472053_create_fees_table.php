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
    Schema::create('fees', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('institution_id');
      $table->string('title')->unique();
      $table->float('amount', 10, 2);
      $table->string('payment_interval');
      $table->softDeletes();
      $table->timestamps();

      $table
        ->foreign('institution_id')
        ->references('id')
        ->on('institutions')
        ->cascadeOnDelete();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('fees');
  }
};
