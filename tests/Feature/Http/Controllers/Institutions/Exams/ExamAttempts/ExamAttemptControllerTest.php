<?php

use App\Enums\ExamStatus;
use App\Models\CourseSession;
use App\Models\Event;
use App\Models\Exam;
use App\Models\ExamCourseable;
use App\Models\Institution;
use App\Models\Question;
use App\Models\Student;
use App\Models\TheoryQuestion;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;

beforeEach(function () {
  $this->institution = Institution::factory()->create();
  $this->event = Event::factory()
    ->institution($this->institution)
    ->started()
    ->create(['duration' => 60]);
  $this->admin = User::factory()
    ->admin($this->institution)
    ->create();
  $this->teacher = User::factory()
    ->teacher($this->institution)
    ->create();
  $this->student = Student::factory()
    ->withInstitution($this->institution)
    ->create();
  $this->studentUser = $this->student->user;
  $this->courseSession = CourseSession::factory()
    ->institution($this->institution)
    ->create();
  $this->questions = Question::factory()
    ->count(2)
    ->courseable($this->courseSession)
    ->sequence(['answer' => 'A'], ['answer' => 'B'])
    ->create();
  $this->theoryQuestion = TheoryQuestion::factory()
    ->courseable($this->courseSession)
    ->create();
  $this->exam = Exam::factory()
    ->event($this->event)
    ->examable($this->student)
    ->status(ExamStatus::Pending)
    ->create();
  ExamCourseable::factory()
    ->exam($this->exam)
    ->courseable($this->courseSession)
    ->create();
});

it('starts or resumes an exam attempt from the display page', function () {
  actingAs($this->studentUser)
    ->get(
      route('institutions.display-exam-page', [
        $this->institution,
        $this->exam->exam_no
      ])
    )
    ->assertOk();

  $this->exam->refresh();

  expect($this->exam->status)
    ->toBe(ExamStatus::Active)
    ->and($this->exam->last_activity_at)
    ->not->toBeNull()
    ->and($this->exam->last_ping_at)
    ->not->toBeNull();
});

it('saves an answer and updates last activity', function () {
  $this->exam
    ->fill([
      'status' => ExamStatus::Active,
      'end_time' => now()->addMinutes(30)
    ])
    ->save();
  $question = $this->questions->first();

  actingAs($this->studentUser)
    ->postJson(
      instRoute(
        'exam-attempts.answers.store',
        [$this->exam],
        $this->institution
      ),
      [
        'attempts' => [$question->id => 'A'],
        'current_question_index' => 0
      ]
    )
    ->assertOk();

  assertDatabaseHas('exam_question_attempts', [
    'exam_id' => $this->exam->id,
    'questionable_type' => (new Question())->getMorphClass(),
    'questionable_id' => $question->id,
    'answer' => 'A',
    'is_answered' => true
  ]);

  $this->exam->refresh();
  expect($this->exam->last_activity_at)
    ->not->toBeNull()
    ->and($this->exam->last_ping_at)
    ->not->toBeNull()
    ->and($this->exam->last_questionable_id)
    ->toBe($question->id)
    ->and($this->exam->current_question_index)
    ->toBe(0);
});

it('saves theory answers with polymorphic question identity', function () {
  $this->exam
    ->fill([
      'status' => ExamStatus::Active,
      'end_time' => now()->addMinutes(30)
    ])
    ->save();

  actingAs($this->studentUser)
    ->postJson(
      instRoute(
        'exam-attempts.answers.store',
        [$this->exam],
        $this->institution
      ),
      ['attempts' => ['theory-' . $this->theoryQuestion->id => 'My answer']]
    )
    ->assertOk();

  assertDatabaseHas('exam_question_attempts', [
    'exam_id' => $this->exam->id,
    'questionable_type' => (new TheoryQuestion())->getMorphClass(),
    'questionable_id' => $this->theoryQuestion->id,
    'answer' => 'My answer',
    'is_answered' => true
  ]);
});

it(
  'updates lightweight ping activity without creating answer rows',
  function () {
    $this->exam
      ->fill([
        'status' => ExamStatus::Active,
        'end_time' => now()->addMinutes(30)
      ])
      ->save();

    actingAs($this->studentUser)
      ->postJson(
        instRoute('exam-attempts.ping', [$this->exam], $this->institution)
      )
      ->assertOk();

    $this->exam->refresh();

    expect($this->exam->last_ping_at)
      ->not->toBeNull()
      ->and($this->exam->questionAttempts()->count())
      ->toBe(0);
  }
);

it(
  'prevents a student from updating another student exam attempt',
  function () {
    $otherStudent = Student::factory()
      ->withInstitution($this->institution)
      ->create();

    actingAs($otherStudent->user)
      ->postJson(
        instRoute(
          'exam-attempts.answers.store',
          [$this->exam],
          $this->institution
        ),
        ['attempts' => [$this->questions->first()->id => 'A']]
      )
      ->assertForbidden();
  }
);

it('prevents submitted attempts from being modified', function () {
  $this->exam
    ->fill([
      'status' => ExamStatus::Ended,
      'submitted_at' => now()
    ])
    ->save();

  actingAs($this->studentUser)
    ->postJson(
      instRoute(
        'exam-attempts.answers.store',
        [$this->exam],
        $this->institution
      ),
      ['attempts' => [$this->questions->first()->id => 'A']]
    )
    ->assertStatus(409);
});

it('allows teachers and admins to view activity summary', function () {
  $this->exam
    ->fill([
      'status' => ExamStatus::Active,
      'end_time' => now()->addMinutes(30)
    ])
    ->save();

  actingAs($this->studentUser)->postJson(
    instRoute('exam-attempts.answers.store', [$this->exam], $this->institution),
    ['attempts' => [$this->questions->first()->id => 'A']]
  );

  actingAs($this->teacher)
    ->getJson(
      instRoute('events.attempt-activity', [$this->event], $this->institution)
    )
    ->assertOk()
    ->assertJsonPath('attempts.0.answered_questions_count', 1)
    ->assertJsonPath('attempts.0.total_questions_count', 3)
    ->assertJsonPath('attempts.0.progress_percentage', 33.3);

  actingAs($this->admin)
    ->getJson(
      instRoute('events.attempt-activity', [$this->event], $this->institution)
    )
    ->assertOk();
});

it('prevents students from viewing activity summary', function () {
  actingAs($this->studentUser)
    ->getJson(
      instRoute('events.attempt-activity', [$this->event], $this->institution)
    )
    ->assertForbidden();
});
