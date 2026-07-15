<?php

use App\Enums\TermType;
use App\Models\AcademicSession;
use App\Models\Classification;
use App\Models\Course;
use App\Models\CourseResult;
use App\Models\Institution;
use App\Models\Student;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('returns student subject scores and positions across all terms', function () {
  $institution = Institution::factory()->create();
  $user = User::factory()
    ->admin($institution)
    ->create();
  $classification = Classification::factory()
    ->withInstitution($institution)
    ->create();
  $session = AcademicSession::factory()->create();
  $course = Course::factory()
    ->withInstitution($institution)
    ->create(['title' => 'Mathematics', 'code' => 'MTH']);

  $firstStudent = Student::factory()
    ->withInstitution($institution, $classification)
    ->create();
  $firstStudent->user->update([
    'first_name' => 'Ada',
    'other_names' => null,
    'last_name' => 'Brown'
  ]);

  $secondStudent = Student::factory()
    ->withInstitution($institution, $classification)
    ->create();
  $secondStudent->user->update([
    'first_name' => 'Bayo',
    'other_names' => null,
    'last_name' => 'Clark'
  ]);

  $thirdStudent = Student::factory()
    ->withInstitution($institution, $classification)
    ->create();
  $thirdStudent->user->update([
    'first_name' => 'Chika',
    'other_names' => null,
    'last_name' => 'Dane'
  ]);

  $scores = [
    TermType::First->value => [
      $firstStudent->id => 0,
      $secondStudent->id => 60
    ],
    TermType::Second->value => [
      $secondStudent->id => 70
    ],
    TermType::Third->value => [
      $firstStudent->id => 90
    ]
  ];

  foreach ($scores as $term => $studentScores) {
    foreach ($studentScores as $studentId => $score) {
      CourseResult::factory()->create([
        'institution_id' => $institution->id,
        'classification_id' => $classification->id,
        'academic_session_id' => $session->id,
        'student_id' => $studentId,
        'teacher_user_id' => $user->id,
        'course_id' => $course->id,
        'term' => $term,
        'for_mid_term' => false,
        'result' => $score,
        'exam' => $score
      ]);
    }
  }

  $this->actingAs($user)
    ->get(
      route('institutions.reports.single-subject-report', [
        $institution->uuid,
        'classification' => $classification->id,
        'academicSession' => $session->id,
        'course' => $course->id
      ])
    )
    ->assertOk()
    ->assertInertia(
      fn(Assert $page) => $page
        ->component('institutions/reports/single-subject-report-sheet')
        ->where('classification.id', $classification->id)
        ->where('academicSession.id', $session->id)
        ->where('course.title', 'Mathematics')
        ->has('singleSubjectReport', 3)
        ->where('singleSubjectReport.0.student.user.full_name', 'Ada Brown')
        ->where('singleSubjectReport.0.term_results.first.score', 0)
        ->where('singleSubjectReport.0.term_results.first.position', 2)
        ->where('singleSubjectReport.0.term_results.second.score', null)
        ->where('singleSubjectReport.0.term_results.third.score', 90)
        ->where('singleSubjectReport.0.term_results.third.position', 1)
        ->where('singleSubjectReport.0.overall.score', 45)
        ->where('singleSubjectReport.0.overall.position', 2)
        ->where('singleSubjectReport.1.student.user.full_name', 'Bayo Clark')
        ->where('singleSubjectReport.1.term_results.first.score', 60)
        ->where('singleSubjectReport.1.term_results.first.position', 1)
        ->where('singleSubjectReport.1.term_results.second.score', 70)
        ->where('singleSubjectReport.1.term_results.second.position', 1)
        ->where('singleSubjectReport.1.overall.score', 65)
        ->where('singleSubjectReport.1.overall.position', 1)
        ->where('singleSubjectReport.2.student.user.full_name', 'Chika Dane')
        ->where('singleSubjectReport.2.term_results.first.score', null)
        ->where('singleSubjectReport.2.overall.score', null)
        ->where('singleSubjectReport.2.overall.position', null)
    );
});
