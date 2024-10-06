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
    Schema::create('student_class_movements', function (Blueprint $table) {
      $table->bigIncrements('id');
      $table->unsignedBigInteger('institution_id');
      $table->unsignedBigInteger('student_id');
      $table->unsignedBigInteger('source_classification_id');
      $table->unsignedBigInteger('destination_classification_id')->nullable();
      $table->unsignedBigInteger('user_id');
      $table->text('note')->nullable();
      $table->string('batch_no')->nullable();

      $table->timestamps();
      $table
        ->foreign('institution_id')
        ->references('id')
        ->on('institutions')
        ->cascadeOnDelete();
      $table
        ->foreign('student_id')
        ->references('id')
        ->on('students')
        ->cascadeOnDelete();
      $table
        ->foreign('source_classification_id')
        ->references('id')
        ->on('classifications')
        ->cascadeOnDelete();
      $table
        ->foreign('destination_classification_id')
        ->references('id')
        ->on('classifications')
        ->cascadeOnDelete();
      $table
        ->foreign('user_id')
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
    Schema::dropIfExists('student_class_movements');
  }
};
