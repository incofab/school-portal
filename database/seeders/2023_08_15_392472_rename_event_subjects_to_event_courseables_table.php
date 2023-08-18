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
    Schema::table('event_subjects', function (Blueprint $table) {
      $table->dropForeign(['course_session_id']);
      $table->dropForeign(['event_id']);
      $table->dropColumn(['course_session_id']);
    });

    Schema::rename('event_subjects', 'event_courseables');

    Schema::table('event_courseables', function (Blueprint $table) {
      $table->nullableMorphs('courseable');
      $table
        ->foreign('event_id')
        ->references('id')
        ->on('events')
        ->cascadeOnDelete();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('event_courseables', function (Blueprint $table) {
      $table->dropMorphs('courseable');
      $table->dropForeign(['event_id']);
    });

    Schema::rename('event_courseables', 'event_subjects');

    Schema::table('event_subjects', function (Blueprint $table) {
      $table->unsignedBigInteger('course_session_id')->nullable();
      $table
        ->foreign('course_session_id')
        ->references('id')
        ->on('course_sessions')
        ->onDelete('cascade')
        ->onUpdate('cascade');

      $table
        ->foreign('event_id')
        ->references('id')
        ->on('events')
        ->cascadeOnDelete();
    });
  }
};
