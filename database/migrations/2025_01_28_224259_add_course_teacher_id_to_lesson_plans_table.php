<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lesson_plans', function (Blueprint $table) {
            $table->foreignId('course_teacher_id')->after('institution_id')->constrained()->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('lesson_plans', function (Blueprint $table) {
            $table->dropForeign(['course_teacher_id']);
            $table->dropColumn('course_teacher_id');
        });
    }
};
