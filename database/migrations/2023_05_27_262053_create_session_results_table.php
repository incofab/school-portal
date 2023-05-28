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
    Schema::create('session_results', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('institution_id');
      $table->unsignedBigInteger('student_id');
      $table->unsignedBigInteger('classification_id');
      $table->unsignedBigInteger('academic_session_id');
      $table->float('result');
      $table->float('average')->default(0);
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
    Schema::dropIfExists('session_results');
  }
};
