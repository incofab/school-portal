<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
    Schema::create('exam_subjects', function (Blueprint $table) {
      $table->id('id');

      $table->unsignedBigInteger('exam_id');
      $table->unsignedBigInteger('course_session_id')->nullable(true);
      $table->unsignedInteger('score', false, true)->nullable(true);
      $table->unsignedInteger('num_of_questions', false, true)->nullable(true);
      $table->string('status')->default('active');

      $table
        ->foreign('exam_id')
        ->references('id')
        ->on('exams')
        ->cascadeOnDelete();

      $table
        ->foreign('course_session_id')
        ->references('id')
        ->on('course_sessions')
        ->cascadeOnDelete();

      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
    Schema::dropIfExists('exam_subjects');
  }
};
