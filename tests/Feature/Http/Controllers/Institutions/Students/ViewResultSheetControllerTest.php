<?php

use App\Enums\TermType;
use App\Models\AcademicSession;
use App\Models\Classification;
use App\Models\ClassResultInfo;
use App\Models\Course;
use App\Models\CourseResult;
use App\Models\CourseResultInfo;
use App\Models\Institution;
use App\Models\ResultPublication;
use App\Models\Student;
use App\Models\TermResult;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

it('exposes cumulative subject averages on result sheet data', function () {
  $institution = Institution::factory()->create();
  $academicSession = AcademicSession::factory()->create();
  $classification = Classification::factory()
    ->withInstitution($institution)
    ->create();
  $student = Student::factory()
    ->withInstitution($institution, $classification)
    ->create();
  $course = Course::factory()
    ->withInstitution($institution)
    ->create(['title' => 'Mathematics']);
  $otherCourse = Course::factory()
    ->withInstitution($institution)
    ->create(['title' => 'English Language']);
  $resultPublication = ResultPublication::factory()->create([
    'institution_id' => $institution->id,
    'institution_group_id' => $institution->institution_group_id,
    'academic_session_id' => $academicSession->id,
    'staff_user_id' => $institution->user_id,
    'term' => TermType::Third->value
  ]);

  TermResult::query()->create([
    'institution_id' => $institution->id,
    'student_id' => $student->id,
    'classification_id' => $classification->id,
    'academic_session_id' => $academicSession->id,
    'term' => TermType::Third->value,
    'for_mid_term' => false,
    'total_score' => 155,
    'average' => 77.5,
    'position' => 1,
    'is_activated' => true,
    'result_publication_id' => $resultPublication->id
  ]);

  ClassResultInfo::query()->create([
    'institution_id' => $institution->id,
    'classification_id' => $classification->id,
    'academic_session_id' => $academicSession->id,
    'term' => TermType::Third->value,
    'for_mid_term' => false,
    'num_of_students' => 1,
    'num_of_courses' => 2,
    'total_score' => 155,
    'max_obtainable_score' => 200,
    'average' => 77.5,
    'max_score' => 90,
    'min_score' => 65
  ]);

  CourseResultInfo::factory()->create([
    'institution_id' => $institution->id,
    'course_id' => $course->id,
    'classification_id' => $classification->id,
    'academic_session_id' => $academicSession->id,
    'term' => TermType::Third->value,
    'for_mid_term' => false,
    'average' => 75
  ]);
  CourseResultInfo::factory()->create([
    'institution_id' => $institution->id,
    'course_id' => $otherCourse->id,
    'classification_id' => $classification->id,
    'academic_session_id' => $academicSession->id,
    'term' => TermType::Third->value,
    'for_mid_term' => false,
    'average' => 80
  ]);

  foreach (
    [
      TermType::First->value => 60,
      TermType::Second->value => 75,
      TermType::Third->value => 90
    ]
    as $term => $score
  ) {
    CourseResult::query()->create([
      'institution_id' => $institution->id,
      'student_id' => $student->id,
      'teacher_user_id' => $institution->user_id,
      'course_id' => $course->id,
      'classification_id' => $classification->id,
      'academic_session_id' => $academicSession->id,
      'term' => $term,
      'for_mid_term' => false,
      'assessment_values' => [],
      'exam' => $score,
      'result' => $score,
      'grade' => 'A',
      'remark' => 'Excellent',
      'position' => 1
    ]);
  }

  CourseResult::query()->create([
    'institution_id' => $institution->id,
    'student_id' => $student->id,
    'teacher_user_id' => $institution->user_id,
    'course_id' => $otherCourse->id,
    'classification_id' => $classification->id,
    'academic_session_id' => $academicSession->id,
    'term' => TermType::Third->value,
    'for_mid_term' => false,
    'assessment_values' => [],
    'exam' => 65,
    'result' => 65,
    'grade' => 'B',
    'remark' => 'Very Good',
    'position' => 1
  ]);

  actingAs($institution->createdBy)
    ->get(
      route('institutions.students.result-sheet', [
        $institution->uuid,
        $student,
        $classification,
        $academicSession,
        TermType::Third->value,
        0
      ])
    )
    ->assertOk()
    ->assertInertia(
      fn(Assert $page) => $page
        ->where("subjectCumulativeAverages.{$course->id}", 75)
        ->where("subjectCumulativeAverages.{$otherCourse->id}", 65)
    );
});
