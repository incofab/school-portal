<?php

use App\Enums\AssignmentStatus;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('course_teacher_id')->nullable();
            $table->unsignedBigInteger('institution_id');
            $table->unsignedBigInteger('course_id')->nullable();
            $table->unsignedBigInteger('classification_id')->nullable();
            $table->unsignedBigInteger('academic_session_id');
            $table->text('term')->nullable();
            $table->string('status')->default(AssignmentStatus::Active->value);
            $table->integer('max_score')->nullable();
            $table->text('content');
            $table->dateTime('expires_at')->nullable();
            $table->timestamps();

            $table
                ->foreign('course_teacher_id')
                ->references('id')
                ->on('course_teachers')
                ->cascadeOnDelete();

            $table
                ->foreign('academic_session_id')
                ->references('id')
                ->on('academic_sessions')
                ->cascadeOnDelete();

            $table
                ->foreign('institution_id')
                ->references('id')
                ->on('institutions')
                ->cascadeOnDelete();

            $table
                ->foreign('course_id')
                ->references('id')
                ->on('courses')
                ->nullOnDelete();

            $table
                ->foreign('classification_id')
                ->references('id')
                ->on('classifications')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignments');
    }
};
