<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::table('exams', function (Blueprint $table) {
      $table
        ->timestamp('last_activity_at')
        ->nullable()
        ->after('end_time');
      $table
        ->timestamp('last_ping_at')
        ->nullable()
        ->after('last_activity_at');
      $table->nullableMorphs('last_questionable');
      $table
        ->unsignedInteger('current_question_index')
        ->nullable()
        ->after('last_questionable_id');
      $table
        ->timestamp('submitted_at')
        ->nullable()
        ->after('status');

      $table->index(['institution_id', 'event_id', 'status']);
      $table->index(['event_id', 'submitted_at']);
      $table->index('last_ping_at');
    });

    Schema::create('exam_question_attempts', function (Blueprint $table) {
      $table->id();
      $table
        ->foreignId('exam_id')
        ->constrained()
        ->cascadeOnDelete();
      $table->unsignedBigInteger('institution_id');
      $table->morphs('questionable');
      $table->longText('answer')->nullable();
      $table->boolean('is_answered')->default(false);
      $table->timestamp('answered_at')->nullable();
      $table->timestamps();

      $table
        ->foreign('institution_id')
        ->references('id')
        ->on('institutions')
        ->cascadeOnDelete()
        ->cascadeOnUpdate();
      $table->unique(
        ['exam_id', 'questionable_type', 'questionable_id'],
        'exam_question_attempts_exam_question_unique'
      );
      $table->index(['institution_id', 'exam_id']);
      $table->index(['exam_id', 'is_answered']);
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('exam_question_attempts');

    Schema::table('exams', function (Blueprint $table) {
      $table->dropIndex(['institution_id', 'event_id', 'status']);
      $table->dropIndex(['event_id', 'submitted_at']);
      $table->dropIndex(['last_ping_at']);
      $table->dropColumn([
        'last_activity_at',
        'last_ping_at',
        'last_questionable_type',
        'last_questionable_id',
        'current_question_index',
        'submitted_at'
      ]);
    });
  }
};
