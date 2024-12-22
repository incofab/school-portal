<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('note_topics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_group_id')->constrained('institution_groups')->cascadeOnDelete();
            $table->foreignId('institution_id')->constrained('institutions')->cascadeOnDelete();
            $table->foreignId('course_teacher_id')->constrained('course_teachers')->cascadeOnDelete(); //Created_by
            $table->foreignId('classification_group_id')->constrained('classification_groups')->cascadeOnDelete();
            $table->foreignId('classification_id')->constrained('classifications')->cascadeOnDelete();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->string('term')->nullable();
            $table->string('title');
            $table->text('content');
            $table->string('status'); //'published' or 'draft'
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('note_topics');
    }
};