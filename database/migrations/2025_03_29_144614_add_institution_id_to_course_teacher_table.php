<?php

use App\Models\CourseTeacher;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::table('course_teachers', function (Blueprint $table) {
      $table
        ->unsignedBigInteger('institution_id')
        ->nullable()
        ->after('id');

      $table
        ->foreign('institution_id')
        ->references('id')
        ->on('institutions')
        ->cascadeOnDelete();
    });

    // Populate institution_id based on classification_id->institution_id
    foreach (CourseTeacher::all() as $courseTeacher) {
      $courseTeacher->institution_id =
        $courseTeacher->classification->institution_id;
      $courseTeacher->save();
    }
  }

  public function down(): void
  {
    Schema::table('course_teachers', function (Blueprint $table) {
      $table->dropForeign(['institution_id']);
      $table->dropColumn('institution_id');
    });
  }
};
