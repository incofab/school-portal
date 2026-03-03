<?php

use App\Enums\TermType;
use App\Models\AcademicSession;
use App\Models\Assessment;
use App\Models\Course;
use App\Models\CourseResult;
use App\Models\CourseSession;
use App\Models\CourseTeacher;
use App\Models\Event;
use App\Models\EventCourseable;
use App\Models\Exam;
use App\Models\ExamCourseable;
use App\Models\Institution;
use App\Models\Student;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertModelExists;
use function PHPUnit\Framework\assertNotNull;

beforeEach(function () {
  $this->institution = Institution::factory()->create();
  $this->admin = $this->institution->createdBy;
  $this->event = Event::factory()->institution($this->institution)->create();
  $this->academicSession = AcademicSession::factory()->create();
  $this->term = TermType::First->value;
  $this->assessment = Assessment::first();
  $this->student = Student::factory()
    ->withInstitution($this->institution)
    ->create();

  $this->courseOne = Course::factory()
    ->withInstitution($this->institution)
    ->create();
  $this->courseTwo = Course::factory()
    ->withInstitution($this->institution)
    ->create();

  $this->sessionOne = CourseSession::factory()
    ->institution($this->institution)
    ->course($this->courseOne)
    ->create();
  $this->sessionTwo = CourseSession::factory()
    ->institution($this->institution)
    ->course($this->courseTwo)
    ->create();

  $this->eventCourseableOne = EventCourseable::factory()
    ->event($this->event)
    ->create([
      'courseable_id' => $this->sessionOne->id,
      'courseable_type' => $this->sessionOne->getMorphClass()
    ]);
  $this->eventCourseableTwo = EventCourseable::factory()
    ->event($this->event)
    ->create([
      'courseable_id' => $this->sessionTwo->id,
      'courseable_type' => $this->sessionTwo->getMorphClass()
    ]);

  $this->courseTeacherOne = CourseTeacher::factory()
    ->withInstitution($this->institution)
    ->create(['course_id' => $this->courseOne->id]);
  $this->courseTeacherTwo = CourseTeacher::factory()
    ->withInstitution($this->institution)
    ->create(['course_id' => $this->courseTwo->id]);

  $this->exam = Exam::factory()
    ->event($this->event)
    ->examable($this->student)
    ->create();
  $this->examCourseableOne = ExamCourseable::factory()
    ->exam($this->exam)
    ->courseable($this->sessionOne)
    ->create(['score' => 42]);
  $this->examCourseableTwo = ExamCourseable::factory()
    ->exam($this->exam)
    ->courseable($this->sessionTwo)
    ->create(['score' => 67]);

  $this->route = route('institutions.events.transfer-results-multiple.store', [
    $this->institution->uuid,
    $this->event->id
  ]);
});

it('transfers event results for multiple event courses', function () {
  actingAs($this->admin)
    ->post($this->route, [
      'academic_session_id' => $this->academicSession->id,
      'term' => $this->term,
      'event_courseables' => [
        [
          'event_courseable_id' => $this->eventCourseableOne->id,
          'course_teacher_id' => $this->courseTeacherOne->id,
          'assessment_id' => null,
          'for_mid_term' => false
        ],
        [
          'event_courseable_id' => $this->eventCourseableTwo->id,
          'course_teacher_id' => $this->courseTeacherTwo->id,
          'assessment_id' => $this->assessment->id,
          'for_mid_term' => true
        ]
      ]
    ])
    ->assertStatus(200);

  $courseResultOne = CourseResult::where([
    'student_id' => $this->student->id,
    'course_id' => $this->courseTeacherOne->course_id,
    'classification_id' => $this->courseTeacherOne->classification_id,
    'academic_session_id' => $this->academicSession->id,
    'term' => $this->term,
    'for_mid_term' => false
  ])->first();

  $courseResultTwo = CourseResult::where([
    'student_id' => $this->student->id,
    'course_id' => $this->courseTeacherTwo->course_id,
    'classification_id' => $this->courseTeacherTwo->classification_id,
    'academic_session_id' => $this->academicSession->id,
    'term' => $this->term,
    'for_mid_term' => true
  ])->first();

  assertModelExists($courseResultOne);
  assertModelExists($courseResultTwo);

  expect($courseResultOne->exam)->toBe(
    floatval($this->examCourseableOne->score)
  );
  expect($courseResultTwo->assessment_values[$this->assessment->raw_title])->toBe(
    $this->examCourseableTwo->score
  );
  assertNotNull($this->event->fresh()->transferred_at);
});
