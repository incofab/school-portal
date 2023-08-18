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
    Schema::table('exam_subjects', function (Blueprint $table) {
      $table->dropForeign(['exam_id']);
      $table->dropForeign(['course_session_id']);
      $table->dropColumn(['course_session_id']);
    });

    Schema::rename('exam_subjects', 'exam_courseables');

    Schema::table('exam_courseables', function (Blueprint $table) {
      $table->nullableMorphs('courseable');
      $table
        ->foreign('exam_id')
        ->references('id')
        ->on('exams')
        ->cascadeOnDelete();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('exam_courseables', function (Blueprint $table) {
      $table->dropMorphs('courseable');
      $table->dropForeign(['exam_id']);
    });

    Schema::rename('exam_courseables', 'exam_subjects');

    Schema::table('exam_subjects', function (Blueprint $table) {
      $table->unsignedBigInteger('course_session_id')->nullable();
      $table
        ->foreign('course_session_id')
        ->references('id')
        ->on('course_sessions')
        ->onDelete('cascade')
        ->onUpdate('cascade');

      $table
        ->foreign('exam_id')
        ->references('id')
        ->on('exams')
        ->cascadeOnDelete();
    });
  }
};
