<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('course_results', function (Blueprint $table) {
            $table->unique(
                [
                    'course_id',
                    'student_id',
                    'classification_id',
                    'academic_session_id',
                    'term',
                    'for_mid_term',
                ],
                'course_results_unique_student_course_term'
            );

            $table->index(
                [
                    'institution_id',
                    'course_id',
                    'classification_id',
                    'academic_session_id',
                    'term',
                    'for_mid_term',
                ],
                'course_results_class_course_lookup_index'
            );
        });

        Schema::table('course_result_info', function (Blueprint $table) {
            $table->unique(
                [
                    'institution_id',
                    'course_id',
                    'classification_id',
                    'academic_session_id',
                    'term',
                    'for_mid_term',
                ],
                'course_result_info_unique_class_course_term'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_result_info', function (Blueprint $table) {
            $table->dropUnique('course_result_info_unique_class_course_term');
        });

        Schema::table('course_results', function (Blueprint $table) {
            $table->dropIndex('course_results_class_course_lookup_index');
            $table->dropUnique('course_results_unique_student_course_term');
        });
    }
};
