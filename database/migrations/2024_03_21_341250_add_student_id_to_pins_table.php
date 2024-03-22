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
    Schema::table('pins', function (Blueprint $table) {
      $table->unsignedBigInteger('student_id')->nullable();
      $table->unsignedBigInteger('academic_session_id')->nullable();
      $table->string('term')->nullable();
      $table
        ->unsignedBigInteger('pin_generator_id')
        ->nullable()
        ->change();

      $table
        ->foreign('student_id')
        ->references('id')
        ->on('students')
        ->nullOnDelete();
      $table
        ->foreign('academic_session_id')
        ->references('id')
        ->on('academic_sessions')
        ->nullOnDelete();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('pins', function (Blueprint $table) {
      $table->dropForeign(['student_id']);
      $table->dropForeign(['academic_session_id']);
      $table->dropColumn(['student_id', 'academic_session_id', 'term']);

      // This will throw an exception if there's a row that constains a null value for this column
      // $table
      //   ->unsignedBigInteger('pin_generator_id')
      //   ->nullable(false)
      //   ->change();
    });
  }
};
