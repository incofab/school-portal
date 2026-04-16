<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::table('exam_courseables', function (Blueprint $table) {
      $table
        ->float('theory_score')
        ->default(0)
        ->after('num_of_questions');
      $table
        ->float('theory_max_score')
        ->default(0)
        ->after('theory_score');
      $table
        ->unsignedInteger('theory_num_of_questions')
        ->default(0)
        ->after('theory_max_score');
      $table
        ->json('theory_question_scores')
        ->nullable()
        ->after('theory_num_of_questions');
      $table
        ->boolean('theory_evaluated')
        ->default(false)
        ->after('theory_question_scores');
    });

    Schema::table('exams', function (Blueprint $table) {
      $table
        ->float('theory_score')
        ->default(0)
        ->after('attempts');
      $table
        ->float('theory_max_score')
        ->default(0)
        ->after('theory_score');
      $table
        ->boolean('theory_evaluated')
        ->default(false)
        ->after('theory_max_score');
    });
  }

  public function down(): void
  {
    Schema::table('exams', function (Blueprint $table) {
      $table->dropColumn([
        'theory_score',
        'theory_max_score',
        'theory_evaluated'
      ]);
    });

    Schema::table('exam_courseables', function (Blueprint $table) {
      $table->dropColumn([
        'theory_score',
        'theory_max_score',
        'theory_num_of_questions',
        'theory_question_scores',
        'theory_evaluated'
      ]);
    });
  }
};
