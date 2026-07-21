<?php

use App\Enums\TermType;
use App\Models\AcademicSession;
use App\Models\Classification;
use App\Models\Course;
use App\Models\CourseResult;
use App\Models\CourseTeacher;
use App\Models\Institution;
use App\Models\Student;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it(
  'returns class subject result rows for all students and subjects',
  function () {
    $institution = Institution::factory()->create();
    $user = User::factory()
      ->admin($institution)
      ->create();
    $classification = Classification::factory()
      ->withInstitution($institution)
      ->create();
    $session = AcademicSession::factory()->create();
    $mathematics = Course::factory()
      ->withInstitution($institution)
      ->create(['title' => 'Mathematics', 'code' => 'MTH', 'order' => 1]);
    $english = Course::factory()
      ->withInstitution($institution)
      ->create(['title' => 'English', 'code' => 'ENG', 'order' => 2]);

    CourseTeacher::factory()->create([
      'institution_id' => $institution->id,
      'classification_id' => $classification->id,
      'course_id' => $mathematics->id,
      'user_id' => $user->id
    ]);
    CourseTeacher::factory()->create([
      'institution_id' => $institution->id,
      'classification_id' => $classification->id,
      'course_id' => $english->id,
      'user_id' => $user->id
    ]);

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

    $scores = [
      $mathematics->id => [
        $firstStudent->id => [
          TermType::First->value => 80,
          TermType::Second->value => 70,
          TermType::Third->value => 90
        ],
        $secondStudent->id => [
          TermType::First->value => 60,
          TermType::Second->value => 75,
          TermType::Third->value => 75
        ]
      ],
      $english->id => [
        $firstStudent->id => [
          TermType::First->value => 50,
          TermType::Third->value => 70
        ],
        $secondStudent->id => [
          TermType::First->value => 65,
          TermType::Second->value => 65,
          TermType::Third->value => 65
        ]
      ]
    ];

    foreach ($scores as $courseId => $studentScores) {
      foreach ($studentScores as $studentId => $termScores) {
        foreach ($termScores as $term => $score) {
          CourseResult::factory()->create([
            'institution_id' => $institution->id,
            'classification_id' => $classification->id,
            'academic_session_id' => $session->id,
            'student_id' => $studentId,
            'teacher_user_id' => $user->id,
            'course_id' => $courseId,
            'term' => $term,
            'for_mid_term' => false,
            'result' => $score,
            'exam' => $score
          ]);
        }
      }
    }

    $this->actingAs($user)
      ->get(
        route('institutions.reports.class-subject-result-report', [
          $institution->uuid,
          'classification' => $classification->id,
          'academicSession' => $session->id
        ])
      )
      ->assertOk()
      ->assertInertia(
        fn(Assert $page) => $page
          ->component('institutions/reports/class-subject-result-report-sheet')
          ->where('classification.id', $classification->id)
          ->where('academicSession.id', $session->id)
          ->has('classSubjectResultReport.courses', 2)
          ->where('classSubjectResultReport.courses.0.title', 'Mathematics')
          ->where('classSubjectResultReport.courses.1.title', 'English')
          ->has('classSubjectResultReport.students', 2)
          ->where(
            'classSubjectResultReport.students.0.student.user.full_name',
            'Ada Brown'
          )
          ->where(
            "classSubjectResultReport.students.0.subject_results.{$mathematics->id}.terms.first",
            80
          )
          ->where(
            "classSubjectResultReport.students.0.subject_results.{$mathematics->id}.terms.second",
            70
          )
          ->where(
            "classSubjectResultReport.students.0.subject_results.{$mathematics->id}.terms.third",
            90
          )
          ->where(
            "classSubjectResultReport.students.0.subject_results.{$mathematics->id}.total",
            240
          )
          ->where(
            "classSubjectResultReport.students.0.subject_results.{$mathematics->id}.average",
            80
          )
          ->where(
            "classSubjectResultReport.students.0.subject_results.{$mathematics->id}.position",
            1
          )
          ->where(
            "classSubjectResultReport.students.0.subject_results.{$english->id}.terms.second",
            null
          )
          ->where(
            "classSubjectResultReport.students.0.subject_results.{$english->id}.total",
            120
          )
          ->where(
            "classSubjectResultReport.students.0.subject_results.{$english->id}.average",
            60
          )
          ->where(
            "classSubjectResultReport.students.0.subject_results.{$english->id}.position",
            2
          )
          ->where(
            'classSubjectResultReport.students.1.student.user.full_name',
            'Bayo Clark'
          )
          ->where(
            "classSubjectResultReport.students.1.subject_results.{$mathematics->id}.total",
            210
          )
          ->where(
            "classSubjectResultReport.students.1.subject_results.{$mathematics->id}.average",
            70
          )
          ->where(
            "classSubjectResultReport.students.1.subject_results.{$mathematics->id}.position",
            2
          )
          ->where(
            "classSubjectResultReport.students.1.subject_results.{$english->id}.position",
            1
          )
      );
  }
);
