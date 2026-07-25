<?php

use App\Enums\TermType;
use App\Models\AcademicSession;
use App\Models\Assessment;
use App\Models\CourseResult;
use App\Models\CourseTeacher;
use App\Models\Institution;
use App\Models\Student;
use Illuminate\Support\Facades\Artisan;

it(
  'backfills legacy course result assessment keys to stable assessment keys',
  function () {
    $institution = Institution::factory()->create();
    $academicSession = AcademicSession::factory()->create();
    $courseTeacher = CourseTeacher::factory()
      ->withInstitution($institution)
      ->create();
    $student = Student::factory()
      ->withInstitution($institution, $courseTeacher->classification)
      ->create();
    $assessment = Assessment::factory()->create([
      'institution_id' => $institution->id,
      'title' => 'project',
      'term' => TermType::First->value,
      'for_mid_term' => false,
      'max' => 20
    ]);

    $courseResult = CourseResult::query()->create([
      'institution_id' => $institution->id,
      'student_id' => $student->id,
      'teacher_user_id' => $courseTeacher->user_id,
      'course_id' => $courseTeacher->course_id,
      'classification_id' => $courseTeacher->classification_id,
      'academic_session_id' => $academicSession->id,
      'term' => TermType::First->value,
      'for_mid_term' => false,
      'exam' => 60,
      'result' => 75,
      'assessment_values' => ['project' => 15],
      'grade' => 'B'
    ]);

    Artisan::call('course-results:backfill-assessment-keys', [
      '--chunk' => 1
    ]);

    expect($courseResult->fresh()->assessment_values->toArray())->toBe([
      $assessment->assessmentResultKey() => 15
    ]);
  }
);

it('backfills keys for soft deleted assessments', function () {
  $institution = Institution::factory()->create();
  $academicSession = AcademicSession::factory()->create();
  $courseTeacher = CourseTeacher::factory()
    ->withInstitution($institution)
    ->create();
  $student = Student::factory()
    ->withInstitution($institution, $courseTeacher->classification)
    ->create();
  $assessment = Assessment::factory()->create([
    'institution_id' => $institution->id,
    'title' => 'quiz',
    'term' => TermType::First->value,
    'for_mid_term' => false,
    'max' => 10
  ]);
  $assessment->delete();

  $courseResult = CourseResult::query()->create([
    'institution_id' => $institution->id,
    'student_id' => $student->id,
    'teacher_user_id' => $courseTeacher->user_id,
    'course_id' => $courseTeacher->course_id,
    'classification_id' => $courseTeacher->classification_id,
    'academic_session_id' => $academicSession->id,
    'term' => TermType::First->value,
    'for_mid_term' => false,
    'exam' => 60,
    'result' => 68,
    'assessment_values' => ['quiz' => 8],
    'grade' => 'C'
  ]);

  Artisan::call('course-results:backfill-assessment-keys');

  expect($courseResult->fresh()->assessment_values->toArray())->toBe([
    $assessment->assessmentResultKey() => 8
  ]);
});
