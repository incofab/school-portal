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
    Schema::create('pins', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('institution_id');
      $table->unsignedBigInteger('pin_generator_id');
      $table->string('pin')->unique();
      $table->dateTime('used_at')->nullable();
      // $table->string('status')->default(PinStatus::Active);
      $table->unsignedBigInteger('term_result_id')->nullable();

      $table->timestamps();

      $table
        ->foreign('institution_id')
        ->references('id')
        ->on('institutions')
        ->cascadeOnDelete();
      $table
        ->foreign('pin_generator_id')
        ->references('id')
        ->on('pin_generators')
        ->cascadeOnDelete();
      $table
        ->foreign('term_result_id')
        ->references('id')
        ->on('term_results')
        ->cascadeOnDelete();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('pins');
  }
};
