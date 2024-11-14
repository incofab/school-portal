<?php

use App\Enums\InstitutionUserType;
use App\Enums\TermType;
use App\Models\AcademicSession;
use App\Models\Assessment;
use App\Models\Course;
use App\Models\CourseResult;
use App\Models\CourseSession;
use App\Models\CourseTeacher;
use App\Models\Event;
use App\Models\Exam;
use App\Models\ExamCourseable;
use App\Models\Institution;
use App\Models\Student;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertModelExists;
use function PHPUnit\Framework\assertNotNull;
use function PHPUnit\Framework\assertNull;

beforeEach(function () {
  $this->institution = Institution::factory()->create();
  $this->admin = $this->institution->createdBy;
  $this->event = Event::factory()
    ->institution($this->institution)
    ->eventCourseables()
    ->create();
  $this->academicSession = AcademicSession::factory()->create();
  $this->term = TermType::First->value;
  $this->assessment = Assessment::first();
  $this->student = Student::factory()
    ->withInstitution($this->institution)
    ->create();
  $this->course = Course::factory()
    ->withInstitution($this->institution)
    ->create();
  $this->courseTeacher = CourseTeacher::factory()
    ->withInstitution($this->institution)
    ->create(['course_id' => $this->course->id]);

  $this->exam = Exam::factory()
    ->event($this->event)
    ->examable($this->student)
    ->create();

  $this->examCourseable = ExamCourseable::factory()
    ->exam($this->exam)
    ->courseable(
      CourseSession::factory()
        ->course($this->course)
        ->create()
    )
    ->create();

  $this->route = route('institutions.events.transfer-results', [
    $this->institution->uuid,
    $this->event->id
  ]);

  $this->post = [
    'academic_session_id' => $this->academicSession->id,
    'term' => $this->term,
    'for_mid_term' => false,
    'course_teacher_id' => $this->courseTeacher->id
  ];
});

it('allows only teachers to access this page', function () {
  $nonAdminOrTeacher = User::factory()
    ->institutionUser($this->institution, InstitutionUserType::Accountant)
    ->create();
  actingAs($nonAdminOrTeacher)
    ->post($this->route, $this->post)
    ->assertForbidden();
});

it('transfers event result to course exam score', function () {
  assertNull($this->event->transferred_at);
  actingAs($this->admin)
    ->post($this->route, $this->post)
    ->assertStatus(200);

  $courseResult = CourseResult::where([
    'student_id' => $this->student->id,
    'academic_session_id' => $this->academicSession->id,
    'term' => $this->term
  ])->first();

  assertModelExists($courseResult);
  expect($courseResult->exam)->toBe(floatval($this->examCourseable->score));
  assertNotNull($this->event->fresh()->transferred_at);
});

it('transfers event result to course assessment score', function () {
  actingAs($this->admin)
    ->post($this->route, [
      ...$this->post,
      'assessment_id' => $this->assessment->id
    ])
    ->assertStatus(200);

  $courseResult = CourseResult::where([
    'student_id' => $this->student->id,
    'academic_session_id' => $this->academicSession->id,
    'term' => $this->term
  ])->first();

  assertModelExists($courseResult);
  expect($courseResult->assessment_values[$this->assessment->raw_title])->toBe(
    $this->examCourseable->score
  );
});
