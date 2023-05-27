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
    Schema::create('course_teachers', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('course_id');
      $table->unsignedBigInteger('user_id');
      $table->unsignedBigInteger('classification_id');

      $table->timestamps();

      $table
        ->foreign('course_id')
        ->references('id')
        ->on('courses')
        ->cascadeOnDelete();
      $table
        ->foreign('user_id')
        ->references('id')
        ->on('users')
        ->cascadeOnDelete();
      $table
        ->foreign('classification_id')
        ->references('id')
        ->on('classifications')
        ->cascadeOnDelete();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('course_teachers');
  }
};
