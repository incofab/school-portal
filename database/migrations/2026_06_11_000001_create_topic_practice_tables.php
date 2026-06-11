<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('topic_practice_summaries', function (Blueprint $table) {
      $table->id();
      $table
        ->foreignId('institution_id')
        ->constrained()
        ->cascadeOnDelete();
      $table
        ->foreignId('student_id')
        ->constrained()
        ->cascadeOnDelete();
      $table
        ->foreignId('classification_id')
        ->constrained()
        ->cascadeOnDelete();
      $table
        ->foreignId('course_id')
        ->constrained()
        ->cascadeOnDelete();
      $table
        ->foreignId('topic_id')
        ->constrained()
        ->cascadeOnDelete();
      $table->unsignedInteger('attempts_count')->default(0);
      $table->unsignedInteger('latest_score')->default(0);
      $table->unsignedInteger('latest_questions_count')->default(0);
      $table->decimal('latest_percentage', 5, 2)->default(0);
      $table->unsignedInteger('best_score')->default(0);
      $table->unsignedInteger('best_questions_count')->default(0);
      $table->decimal('best_percentage', 5, 2)->default(0);
      $table->timestamp('last_generated_at')->nullable();
      $table->timestamp('last_submitted_at')->nullable();
      $table->timestamps();

      $table->unique(['student_id', 'topic_id'], 'tps_student_topic_unique');
      $table->index(
        ['institution_id', 'classification_id', 'course_id'],
        'tps_inst_class_course_idx'
      );
      $table->index(['institution_id', 'topic_id'], 'tps_inst_topic_idx');
    });

    Schema::create('topic_practice_attempts', function (Blueprint $table) {
      $table->id();
      $table
        ->foreignId('topic_practice_summary_id')
        ->constrained()
        ->cascadeOnDelete();
      $table
        ->foreignId('institution_id')
        ->constrained()
        ->cascadeOnDelete();
      $table
        ->foreignId('student_id')
        ->constrained()
        ->cascadeOnDelete();
      $table
        ->foreignId('classification_id')
        ->constrained()
        ->cascadeOnDelete();
      $table
        ->foreignId('course_id')
        ->constrained()
        ->cascadeOnDelete();
      $table
        ->foreignId('topic_id')
        ->constrained()
        ->cascadeOnDelete();
      $table->unsignedInteger('attempt_number');
      $table->json('questions');
      $table->json('answers')->nullable();
      $table->unsignedInteger('score')->default(0);
      $table->unsignedInteger('questions_count')->default(0);
      $table->unsignedInteger('answered_questions_count')->default(0);
      $table->decimal('percentage', 5, 2)->default(0);
      $table->timestamp('submitted_at')->nullable();
      $table->timestamps();

      $table->unique(
        ['student_id', 'topic_id', 'attempt_number'],
        'tpa_student_topic_attempt_unique'
      );
      $table->index(
        ['institution_id', 'classification_id', 'course_id'],
        'tpa_inst_class_course_idx'
      );
      $table->index(['institution_id', 'topic_id'], 'tpa_inst_topic_idx');
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('topic_practice_attempts');
    Schema::dropIfExists('topic_practice_summaries');
  }
};
