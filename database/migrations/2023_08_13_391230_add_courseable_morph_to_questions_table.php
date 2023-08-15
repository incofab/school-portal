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
    Schema::table('questions', function (Blueprint $table) {
      $table->dropForeign(['course_session_id']);
      $table->dropColumn(['course_session_id']);

      $table->nullableMorphs('courseable');
      $table->unsignedBigInteger('institution_id')->nullable();

      $table
        ->foreign('institution_id')
        ->references('id')
        ->on('institutions')
        ->onDelete('cascade')
        ->onUpdate('cascade');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('questions', function (Blueprint $table) {
      $table->dropMorphs('courseable');
      $table->dropForeign(['institution_id']);
      $table->dropColumn(['institution_id']);

      $table->unsignedBigInteger('course_session_id')->nullable();
      $table
        ->foreign('course_session_id')
        ->references('id')
        ->on('course_sessions')
        ->onDelete('cascade')
        ->onUpdate('cascade');
    });
  }
};
