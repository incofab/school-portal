<?php

use App\Models\Event;
use App\Models\Exam;
use App\Models\Institution;
use App\Models\Student;
use Illuminate\Testing\Fluent\AssertableJson;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\postJson;

beforeEach(function () {
  $this->institution = Institution::factory()->create();
  $this->admin = $this->institution->createdBy;
  $this->student = Student::factory()
    ->withInstitution($this->institution)
    ->create();
  $this->event = Event::factory()
    ->institution($this->institution)
    ->started()
    ->eventCourseables(1)
    ->create(['num_of_subjects' => 1]);
});

it(
  'allows a student to login and start an exam with correct codes',
  function () {
    $data = [
      'event_code' => $this->event->code,
      'student_code' => $this->student->code
    ];

    // Act
    $response = postJson(route('student.exam.login.store'), $data)->assertOk();
    $exam = Exam::query()
      ->where('examable_id', $this->student->id)
      ->where('examable_type', $this->student->getMorphClass())
      ->first();
    $response->assertJson(
      fn(AssertableJson $json) => $json
        ->where('ok', true)
        ->where('institution.uuid', (string) $this->institution->uuid)
        ->where('exam.exam_no', $exam->exam_no)
        ->etc()
    );
    assertDatabaseHas('exams', [
      'examable_id' => $this->student->id,
      'examable_type' => $this->student->getMorphClass(),
      'event_id' => $this->event->id,
      'status' => \App\Enums\ExamStatus::Active
    ]);
  }
);
