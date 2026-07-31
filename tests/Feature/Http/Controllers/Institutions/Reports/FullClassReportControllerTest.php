<?php

use App\Enums\TermType;
use App\Models\AcademicSession;
use App\Models\Assessment;
use App\Models\Classification;
use App\Models\Course;
use App\Models\CourseResult;
use App\Models\CourseTeacher;
use App\Models\Institution;
use App\Models\Student;
use App\Models\TermResult;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it(
  'returns full class report rows with dynamic assessments and term summaries',
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
    $quiz = Assessment::factory()
      ->withInstitution($institution)
      ->create([
        'title' => 'quiz',
        'term' => TermType::First->value,
        'for_mid_term' => false,
        'max' => 10
      ]);
    $project = Assessment::factory()
      ->withInstitution($institution)
      ->create([
        'title' => 'project_work',
        'term' => TermType::First->value,
        'for_mid_term' => false,
        'max' => 20
      ]);

    foreach ([$mathematics, $english] as $course) {
      CourseTeacher::factory()->create([
        'institution_id' => $institution->id,
        'classification_id' => $classification->id,
        'course_id' => $course->id,
        'user_id' => $user->id
      ]);
    }

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

    TermResult::query()->create([
      'institution_id' => $institution->id,
      'student_id' => $firstStudent->id,
      'classification_id' => $classification->id,
      'academic_session_id' => $session->id,
      'term' => TermType::First->value,
      'for_mid_term' => false,
      'total_score' => 160,
      'average' => 80,
      'position' => 1
    ]);
    TermResult::query()->create([
      'institution_id' => $institution->id,
      'student_id' => $secondStudent->id,
      'classification_id' => $classification->id,
      'academic_session_id' => $session->id,
      'term' => TermType::First->value,
      'for_mid_term' => false,
      'total_score' => 128,
      'average' => 64,
      'position' => 2
    ]);

    CourseResult::query()->create([
      'institution_id' => $institution->id,
      'classification_id' => $classification->id,
      'academic_session_id' => $session->id,
      'student_id' => $firstStudent->id,
      'teacher_user_id' => $user->id,
      'course_id' => $mathematics->id,
      'term' => TermType::First->value,
      'for_mid_term' => false,
      'assessment_values' => [
        $quiz->assessmentResultKey() => 8,
        $project->assessmentResultKey() => 17
      ],
      'exam' => 55,
      'result' => 80,
      'grade' => 'A',
      'remark' => 'Excellent'
    ]);
    CourseResult::query()->create([
      'institution_id' => $institution->id,
      'classification_id' => $classification->id,
      'academic_session_id' => $session->id,
      'student_id' => $firstStudent->id,
      'teacher_user_id' => $user->id,
      'course_id' => $english->id,
      'term' => TermType::First->value,
      'for_mid_term' => false,
      'assessment_values' => [
        $quiz->assessmentResultKey() => 7,
        $project->assessmentResultKey() => 13
      ],
      'exam' => 60,
      'result' => 80,
      'grade' => 'A',
      'remark' => 'Excellent'
    ]);
    CourseResult::query()->create([
      'institution_id' => $institution->id,
      'classification_id' => $classification->id,
      'academic_session_id' => $session->id,
      'student_id' => $secondStudent->id,
      'teacher_user_id' => $user->id,
      'course_id' => $mathematics->id,
      'term' => TermType::First->value,
      'for_mid_term' => false,
      'assessment_values' => [
        $quiz->assessmentResultKey() => 6,
        $project->assessmentResultKey() => 12
      ],
      'exam' => 46,
      'result' => 64,
      'grade' => 'B',
      'remark' => 'Good'
    ]);

    $expectedAssessmentCount = Assessment::getAssessments(
      TermType::First,
      false,
      $classification,
      true
    )->count();

    $this->actingAs($user)
      ->get(
        route('institutions.reports.full-class-report', [
          $institution->uuid,
          'classification' => $classification->id,
          'academicSession' => $session->id,
          'term' => TermType::First->value
        ])
      )
      ->assertOk()
      ->assertInertia(
        fn(Assert $page) => $page
          ->component('institutions/reports/full-class-report-sheet')
          ->where('classification.id', $classification->id)
          ->where('academicSession.id', $session->id)
          ->where('term', TermType::First->value)
          ->has('fullClassReport.courses', 2)
          ->where('fullClassReport.courses.0.title', 'Mathematics')
          ->has(
            'fullClassReport.courses.0.assessments',
            $expectedAssessmentCount
          )
          ->has('fullClassReport.students', 2)
          ->where(
            'fullClassReport.students.0.student.user.full_name',
            'Ada Brown'
          )
          ->where(
            "fullClassReport.students.0.subject_results.{$mathematics->id}.assessments.{$quiz->assessmentResultKey()}",
            8
          )
          ->where(
            "fullClassReport.students.0.subject_results.{$mathematics->id}.exam",
            55
          )
          ->where(
            "fullClassReport.students.0.subject_results.{$mathematics->id}.total",
            80
          )
          ->where('fullClassReport.students.0.overall_total_score', 160)
          ->where('fullClassReport.students.0.average', 80)
          ->where('fullClassReport.students.0.position', 1)
          ->where(
            "fullClassReport.students.1.subject_results.{$english->id}.exam",
            null
          )
          ->where('fullClassReport.students.1.overall_total_score', 64)
          ->where('fullClassReport.students.1.position', 2)
      );
  }
);
