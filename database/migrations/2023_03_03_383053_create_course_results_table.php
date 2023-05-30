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
    Schema::create('course_results', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('institution_id');
      $table->unsignedBigInteger('student_id');
      $table->unsignedBigInteger('teacher_user_id');
      $table->unsignedBigInteger('course_id');
      $table->unsignedBigInteger('classification_id');
      $table->unsignedBigInteger('academic_session_id')->nullable();
      $table->string('term')->nullable();
      $table->float('first_assessment')->default(0);
      $table->float('second_assessment')->default(0);
      $table->float('exam')->default(0);
      $table->float('result');
      // $table->float('average')->default(0);
      $table->unsignedInteger('result_max')->nullable();
      $table->string('grade')->nullable();
      $table->string('remark')->nullable();

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
        ->foreign('teacher_user_id')
        ->references('id')
        ->on('users')
        ->cascadeOnDelete();

      $table
        ->foreign('course_id')
        ->references('id')
        ->on('courses')
        ->cascadeOnDelete();

      $table
        ->foreign('classification_id')
        ->references('id')
        ->on('classifications')
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
    Schema::dropIfExists('course_results');
  }
};
