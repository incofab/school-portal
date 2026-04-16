<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('theory_questions', function (Blueprint $table) {
      $table->id();
      $table
        ->foreignId('institution_id')
        ->constrained()
        ->cascadeOnDelete()
        ->cascadeOnUpdate();
      $table->morphs('courseable');
      $table->unsignedInteger('question_no');
      $table->string('question_sub_number', 20)->nullable();
      $table->longText('question');
      $table->float('marks')->default(1);
      $table->longText('answer');
      $table->longText('marking_scheme')->nullable();
      $table->timestamps();

      // $table->index(['institution_id', 'courseable_type', 'courseable_id']);
      // $table->unique(
      //     ['courseable_type', 'courseable_id', 'question_no', 'question_sub_number'],
      //     'theory_questions_courseable_number_sub_unique'
      // );
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('theory_questions');
  }
};
