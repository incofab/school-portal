<?php

use App\Enums\TermType;
use App\Models\AcademicSession;
use App\Models\Classification;
use App\Models\Course;
use App\Models\CourseResult;
use App\Models\CourseResultInfo;
use App\Models\Institution;
use App\Models\Student;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it(
  'returns cumulative and per-term subject report sections for all terms',
  function () {
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
    $secondStudent = Student::factory()
      ->withInstitution($institution, $classification)
      ->create();

    $scores = [
      TermType::First->value => [
        $firstStudent->id => 70,
        $secondStudent->id => 60
      ],
      TermType::Second->value => [
        $firstStudent->id => 80,
        $secondStudent->id => 70
      ],
      TermType::Third->value => [
        $firstStudent->id => 90,
        $secondStudent->id => 65
      ]
    ];

    foreach ($scores as $term => $studentScores) {
      CourseResultInfo::factory()
        ->withInstitution($institution, $classification, $course, $session)
        ->create([
          'term' => $term,
          'for_mid_term' => false,
          'num_of_students' => count($studentScores),
          'total_score' => array_sum($studentScores),
          'max_obtainable_score' => 100,
          'average' => array_sum($studentScores) / count($studentScores),
          'min_score' => min($studentScores),
          'max_score' => max($studentScores)
        ]);

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
        route('institutions.reports.subject-report', [
          $institution->uuid,
          'classification' => $classification->id,
          'academicSession' => $session->id,
          'term' => 'all-terms'
        ])
      )
      ->assertOk()
      ->assertInertia(
        fn(Assert $page) => $page
          ->component('institutions/reports/subject-report-sheet')
          ->where('term', 'all-terms')
          ->has('subjectReportSections', 4)
          ->where('subjectReportSections.0.key', 'all-terms')
          ->where('subjectReportSections.0.title', 'Cumulative')
          ->where(
            'subjectReportSections.0.subjectReport.0.course.title',
            'Mathematics'
          )
          ->where('subjectReportSections.0.subjectReport.0.num_of_students', 2)
          ->where('subjectReportSections.0.subjectReport.0.total_score', 435)
          ->where(
            'subjectReportSections.0.subjectReport.0.max_obtainable_score',
            300
          )
          ->where('subjectReportSections.0.subjectReport.0.max_score', 240)
          ->where('subjectReportSections.0.subjectReport.0.min_score', 195)
          ->where('subjectReportSections.0.subjectReport.0.average', 217.5)
          ->where('subjectReportSections.0.subjectReport.0.highest_score', 240)
          ->where('subjectReportSections.0.subjectReport.0.lowest_score', 195)
          ->where('subjectReportSections.1.key', 'first')
          ->where('subjectReportSections.2.key', 'second')
          ->where('subjectReportSections.3.key', 'third')
      );
  }
);
