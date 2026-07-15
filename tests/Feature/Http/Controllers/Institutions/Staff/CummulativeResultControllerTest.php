<?php

use App\Enums\TermType;
use App\Models\AcademicSession;
use App\Models\Classification;
use App\Models\Course;
use App\Models\CourseResult;
use App\Models\Institution;
use App\Models\SessionResult;
use App\Models\Student;
use App\Models\TermResult;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;

it(
  'keeps cumulative result rows aligned when students have missing term records',
  function () {
    $institution = Institution::factory()->create();
    $admin = User::factory()
      ->admin($institution)
      ->create();
    $classification = Classification::factory()
      ->withInstitution($institution)
      ->create();
    $academicSession = AcademicSession::factory()->create();
    $firstTermCourse = Course::factory()
      ->withInstitution($institution)
      ->create(['title' => 'Mathematics', 'code' => 'MTH']);
    $secondTermCourse = Course::factory()
      ->withInstitution($institution)
      ->create(['title' => 'English Language', 'code' => 'ENG']);
    $firstStudent = Student::factory()
      ->withInstitution($institution, $classification)
      ->create();
    $secondStudent = Student::factory()
      ->withInstitution($institution, $classification)
      ->create();

    foreach ([$firstStudent, $secondStudent] as $student) {
      SessionResult::query()->create([
        'institution_id' => $institution->id,
        'student_id' => $student->id,
        'classification_id' => $classification->id,
        'academic_session_id' => $academicSession->id,
        'result' => 70,
        'average' => 70,
        'grade' => 'A',
        'remark' => 'Good'
      ]);
    }

    TermResult::query()->create([
      'institution_id' => $institution->id,
      'student_id' => $firstStudent->id,
      'classification_id' => $classification->id,
      'academic_session_id' => $academicSession->id,
      'term' => TermType::First->value,
      'total_score' => 80,
      'average' => 80,
      'position' => 1
    ]);
    TermResult::query()->create([
      'institution_id' => $institution->id,
      'student_id' => $secondStudent->id,
      'classification_id' => $classification->id,
      'academic_session_id' => $academicSession->id,
      'term' => TermType::Second->value,
      'total_score' => 70,
      'average' => 70,
      'position' => 2
    ]);

    CourseResult::query()->create([
      'institution_id' => $institution->id,
      'student_id' => $firstStudent->id,
      'teacher_user_id' => $admin->id,
      'course_id' => $firstTermCourse->id,
      'classification_id' => $classification->id,
      'academic_session_id' => $academicSession->id,
      'term' => TermType::First->value,
      'exam' => 40,
      'result' => 80,
      'grade' => 'A',
      'remark' => 'Good'
    ]);
    CourseResult::query()->create([
      'institution_id' => $institution->id,
      'student_id' => $secondStudent->id,
      'teacher_user_id' => $admin->id,
      'course_id' => $secondTermCourse->id,
      'classification_id' => $classification->id,
      'academic_session_id' => $academicSession->id,
      'term' => TermType::Second->value,
      'exam' => 35,
      'result' => 70,
      'grade' => 'B',
      'remark' => 'Good'
    ]);

    actingAs($admin)
      ->get(
        instRoute('cummulative-result.index', [], $institution) .
          '?' .
          http_build_query([
            'classification' => $classification->id,
            'academicSession' => $academicSession->id
          ])
      )
      ->assertOk()
      ->assertInertia(function (AssertableInertia $page) {
        $page
          ->component('institutions/staff/cummulative-result-sheet')
          ->where('terms', [
            TermType::First->value,
            TermType::Second->value,
            TermType::Third->value
          ])
          ->has('coursesByTerm.first', 1)
          ->has('coursesByTerm.second', 1)
          ->has('sessionResults', 2)
          ->where('sessionResults.0.termResults.first.total_score', 80)
          ->where('sessionResults.0.termResults.second', null)
          ->where('sessionResults.1.termResults.first', null)
          ->where('sessionResults.1.termResults.second.total_score', 70);
      });
  }
);
