<?php

use App\Models\CourseTeacher;
use App\Models\Institution;
use App\Models\LessonPlan;
use App\Models\SchemeOfWork;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

afterEach(function () {
  DB::table('lesson_plans')->delete();
  if (Schema::hasColumn('lesson_plans', 'course_teacher_id')) {
    Schema::table('lesson_plans', fn($table) =>
      $table->dropColumn('course_teacher_id')
    );
  }
});

it('converts legacy lesson plan teacher references and is rerunnable', function () {
  $institution = Institution::factory()->create();
  $courseTeacher = CourseTeacher::factory()
    ->withInstitution($institution)
    ->create();
  $schemeOfWork = SchemeOfWork::factory()->create([
    'institution_id' => $institution->id
  ]);
  $lessonPlan = LessonPlan::factory()
    ->schemeOfWork($schemeOfWork)
    ->create([
      'institution_id' => $institution->id,
      'teacher_user_id' => null
    ]);

  Schema::table('lesson_plans', function ($table) {
    $table->unsignedBigInteger('course_teacher_id')->nullable();
  });
  DB::table('lesson_plans')
    ->where('id', $lessonPlan->id)
    ->update(['course_teacher_id' => $courseTeacher->id]);

  expect(Artisan::call('data:convert-course-teacher-ids', [
    '--force' => true
  ]))->toBe(0);
  expect(Schema::hasColumn('lesson_plans', 'course_teacher_id'))->toBeFalse();
  expect($lessonPlan->fresh()->teacher_user_id)->toBe($courseTeacher->user_id);

  expect(Artisan::call('data:convert-course-teacher-ids', [
    '--force' => true
  ]))->toBe(0);
});

it('retains the legacy column when a course teacher cannot be resolved', function () {
  $institution = Institution::factory()->create();
  $schemeOfWork = SchemeOfWork::factory()->create([
    'institution_id' => $institution->id
  ]);
  $lessonPlan = LessonPlan::factory()
    ->schemeOfWork($schemeOfWork)
    ->create([
      'institution_id' => $institution->id,
      'teacher_user_id' => null
    ]);

  Schema::table('lesson_plans', function ($table) {
    $table->unsignedBigInteger('course_teacher_id')->nullable();
  });
  DB::table('lesson_plans')
    ->where('id', $lessonPlan->id)
    ->update(['course_teacher_id' => 999999999]);

  expect(Artisan::call('data:convert-course-teacher-ids', [
    '--force' => true
  ]))->toBe(1);
  expect(Schema::hasColumn('lesson_plans', 'course_teacher_id'))->toBeTrue();
  expect($lessonPlan->fresh()->teacher_user_id)->toBeNull();
});
