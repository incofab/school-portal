<?php

namespace Tests\Feature\API\OfflineMock;

use App\Enums\ExamStatus;
use App\Models\CourseSession;
use App\Models\Event;
use App\Models\Exam;
use App\Models\ExamCourseable;
use App\Models\Institution;
use App\Models\Student;
use App\Support\MorphMap;

use function Pest\Laravel\postJson;

beforeEach(function () {
  $this->institution = Institution::factory()->create();
  $this->student = Student::factory()
    ->withInstitution($this->institution)
    ->create();

  $this->event = Event::factory()
    ->institution($this->institution)
    ->eventCourseables()
    ->create();
  $this->eventCourseable = $this->event->eventCourseables()->first();
  $this->courseSession = $this->eventCourseable->courseable;
});

it('successfully uploads new exam results for existing students', function () {
  $examData = [
    'event_id' => $this->event->id,
    'exam_no' => "{$this->event->id}-{$this->student->code}",
    'attempts' => [['question_id' => 1, 'answer' => 'A']],
    'time_remaining' => '00:10:00',
    'start_time' => now()->toDateTimeString(),
    'end_time' => now()
      ->addHour()
      ->toDateTimeString(),
    'status' => ExamStatus::Ended->value,
    'num_of_questions' => 10,
    'score' => 80, // Overall exam score
    'exam_courses' => [
      [
        'course_session_id' => $this->courseSession->id,
        'score' => 80,
        'status' => ExamStatus::Ended->value,
        'num_of_questions' => 10
      ]
    ]
  ];

  $payload = ['exams' => [$examData]];

  postJson(
    route('offline-mock.exams.upload', [
      'institution' => $this->institution->code
    ]),
    $payload
  )->assertOk();

  $this->assertDatabaseHas('exams', [
    'event_id' => $this->event->id,
    'exam_no' => $examData['exam_no'],
    'examable_id' => $this->student->id,
    'examable_type' => MorphMap::key(Student::class),
    'status' => $examData['status'],
    'num_of_questions' => $examData['num_of_questions'],
    'score' => $examData['score']
  ]);

  $createdExam = Exam::where('exam_no', $examData['exam_no'])->first();
  // dd($createdExam->toArray());
  expect($createdExam)->not->toBeNull();
  expect($createdExam->attempts?->toArray())->toEqual($examData['attempts']);

  $this->assertDatabaseHas('exam_courseables', [
    'exam_id' => $createdExam->id,
    'courseable_id' => $this->courseSession->id,
    'courseable_type' => MorphMap::key(CourseSession::class),
    'score' => $examData['exam_courses'][0]['score'],
    'status' => $examData['exam_courses'][0]['status'],
    'num_of_questions' => $examData['exam_courses'][0]['num_of_questions']
  ]);
});

it('updates existing exam results if exam_no and event_id match', function () {
  // Initial creation
  $initialExam = Exam::factory()
    ->event($this->event)
    ->examable($this->student)
    ->create([
      'exam_no' => "{$this->event->id}-{$this->student->code}",
      'num_of_questions' => 5,
      'score' => 50,
      'status' => 'pending',
      'attempts' => ['initial attempt']
    ]);
  $examCourseable = ExamCourseable::factory()
    ->exam($initialExam)
    ->courseable($this->courseSession)
    ->create();

  $updatedExamData = [
    'event_id' => $this->event->id,
    'exam_no' => $initialExam->exam_no, // Same exam_no
    'attempts' => [['question_id' => 2, 'answer' => 'B']],
    'status' => ExamStatus::Ended->value,
    'num_of_questions' => 7, // Updated
    'score' => 70, // Updated
    'exam_courses' => [
      [
        'course_session_id' => $this->courseSession->id,
        'score' => 7, // Updated
        'status' => ExamStatus::Ended->value, // Updated
        'num_of_questions' => 7 // Updated
      ]
    ]
  ];
  $payload = ['exams' => [$updatedExamData]];

  $this->postJson(
    route('offline-mock.exams.upload', [
      'institution' => $this->institution->code
    ]),
    $payload
  )->assertOk();

  $this->assertDatabaseCount('exams', 1); // Should not create a new exam
  $this->assertDatabaseHas('exams', [
    'id' => $initialExam->id,
    'status' => ExamStatus::Ended->value,
    'num_of_questions' => 7,
    'score' => 70
  ]);
  $updatedExam = Exam::find($initialExam->id);
  expect($updatedExam->attempts->toArray())->toEqual(
    $updatedExamData['attempts']
  );

  $this->assertDatabaseCount('exam_courseables', 1);
  $this->assertDatabaseHas('exam_courseables', [
    'exam_id' => $initialExam->id,
    'courseable_id' => $this->courseSession->id,
    'score' => 7,
    'status' => ExamStatus::Ended->value,
    'num_of_questions' => 7
  ]);
});

it(
  'skips processing an exam if student code from exam_no is not found',
  function () {
    $examDataNonExistentStudent = [
      'event_id' => $this->event->id,
      'exam_no' => 'EXAM3-NONEXISTENT-003',
      'attempts' => ['attempt'],
      'status' => ExamStatus::Ended->value,
      'exam_courses' => [['course_session_id' => 999, 'score' => 10]] // Dummy course session
    ];
    $payload = ['exams' => [$examDataNonExistentStudent]];

    $this->postJson(
      route('offline-mock.exams.upload', [
        'institution' => $this->institution->code
      ]),
      $payload
    )
      ->assertOk()
      ->assertJson(['message' => 'Exam records updated']);

    $this->assertDatabaseMissing('exams', [
      'exam_no' => $examDataNonExistentStudent['exam_no']
    ]);
  }
);
