<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('theory_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('course_session_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->unsignedInteger('question_number');
            $table->string('question_sub_number', 20)->nullable();
            $table->longText('question');
            $table->float('marks')->default(0);
            $table->longText('answer');
            $table->longText('marking_scheme')->nullable();
            $table->timestamps();

            $table->index(['institution_id', 'course_session_id']);
            $table->unique(
                ['course_session_id', 'question_number', 'question_sub_number'],
                'theory_questions_course_session_number_sub_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('theory_questions');
    }
};
