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
    Schema::create('term_results', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('institution_id');
      $table->unsignedBigInteger('student_id');
      $table->unsignedBigInteger('classification_id');
      $table->unsignedBigInteger('academic_session_id');
      $table->string('term');
      $table->boolean('for_mid_term')->default(false);
      $table->float('total_score');
      $table->unsignedInteger('position');
      $table->float('average')->default(0);
      $table->boolean('is_activated')->default(false);
      $table->string('remark')->nullable();
      $table->text('teacher_comment')->nullable();
      $table->text('principal_comment')->nullable();
      $table->text('general_comment')->nullable();

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
    Schema::dropIfExists('term_results');
  }
};
