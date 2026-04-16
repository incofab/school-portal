<?php

use App\Models\CourseSession;
use App\Models\Event;
use App\Models\EventCourseable;
use App\Models\Exam;
use App\Models\ExamCourseable;
use App\Models\Institution;
use App\Models\TheoryQuestion;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

beforeEach(function () {
  $this->institution = Institution::factory()->create();
  $this->event = Event::factory()
    ->institution($this->institution)
    ->create();
  $this->exam = Exam::factory()
    ->event($this->event)
    ->create();
  $this->admin = User::factory()
    ->admin($this->institution)
    ->create();
});

it('only allows admins to access the controller', function () {
  $nonAdminUser = User::factory()
    ->student($this->institution)
    ->create();

  // Attempt to access the controller as a non-admin
  actingAs($nonAdminUser)
    ->getJson(
      instRoute('exam-courseables.index', [$this->exam], $this->institution)
    )
    ->assertStatus(403);

  // Attempt to access the controller as an admin
  actingAs($this->admin)
    ->getJson(
      instRoute('exam-courseables.index', [$this->exam], $this->institution)
    )
    ->assertStatus(200);
});

it('displays the create exam-courseable form', function () {
  actingAs($this->admin)
    ->getJson(
      instRoute('exam-courseables.create', [$this->exam], $this->institution)
    )
    ->assertStatus(200);
});

it('deletes an exam-courseable', function () {
  $examCourseable = ExamCourseable::factory()
    ->exam($this->exam)
    ->create();
  // Create an exam (if needed)
  actingAs($this->admin)
    ->deleteJson(
      instRoute(
        'exam-courseables.destroy',
        [$examCourseable],
        $this->institution
      )
    )
    ->assertStatus(200);
  assertDatabaseMissing('exam_courseables', ['id' => $examCourseable->id]);
});

it('stores new exam courseables', function () {
  $courseable = CourseSession::factory()
    ->institution($this->institution)
    ->create();
  $eventCourseables = EventCourseable::factory()
    ->count(3)
    ->event($this->event)
    ->create();
  actingAs($this->admin)
    ->postJson(
      instRoute('exam-courseables.store', [$this->exam], $this->institution),
      [
        'courseables' => $eventCourseables
          ->map(
            fn($item) => [
              'courseable_id' => $item->courseable->id,
              'courseable_type' => $item->courseable->getMorphClass()
            ]
          )
          ->toArray()
      ]
    )
    ->assertStatus(200);
  $eventCourseables->map(
    fn($item) => assertDatabaseHas('exam_courseables', [
      'exam_id' => $this->exam->id,
      'courseable_type' => $item->courseable->getMorphClass(),
      'courseable_id' => $item->courseable->id
    ])
  );
});

it('evaluates theory answers for an ended exam courseable', function () {
  $courseable = CourseSession::factory()
    ->institution($this->institution)
    ->create();
  $examCourseable = ExamCourseable::factory()
    ->exam($this->exam)
    ->courseable($courseable)
    ->create([
      'score' => 4,
      'num_of_questions' => 5,
      'theory_score' => 0,
      'theory_max_score' => 8,
      'theory_num_of_questions' => 2,
      'theory_evaluated' => false
    ]);
  $questions = TheoryQuestion::factory()
    ->count(2)
    ->courseable($courseable)
    ->sequence(['marks' => 5], ['marks' => 3])
    ->create();
  $this->exam->update([
    'score' => 0,
    'status' => \App\Enums\ExamStatus::Ended
  ]);

  actingAs($this->admin)
    ->postJson(
      instRoute(
        'exam-courseables.evaluate-theory',
        [$this->exam, $examCourseable],
        $this->institution
      ),
      [
        'scores' => [
          $questions[0]->id => 4,
          $questions[1]->id => 2.5
        ]
      ]
    )
    ->assertStatus(200);

  $examCourseable->refresh();
  $this->exam->refresh();

  expect($examCourseable->theory_score)->toBe(6.5);
  expect($examCourseable->theory_max_score)->toBe(8);
  expect($examCourseable->theory_evaluated)->toBeTrue();
  expect($examCourseable->theory_question_scores)->toBe([
    $questions[0]->id => 4,
    $questions[1]->id => 2.5
  ]);
  expect($this->exam->score)->toBe(0.0);
  expect($this->exam->theory_score)->toBe(6.5);
  expect($this->exam->theory_max_score)->toBe(8.0);
  expect($this->exam->theory_evaluated)->toBeTrue();
});

it('does not allow a theory score above the question mark', function () {
  $courseable = CourseSession::factory()
    ->institution($this->institution)
    ->create();
  $examCourseable = ExamCourseable::factory()
    ->exam($this->exam)
    ->courseable($courseable)
    ->create([
      'theory_num_of_questions' => 1,
      'theory_evaluated' => false
    ]);
  $question = TheoryQuestion::factory()
    ->courseable($courseable)
    ->create(['marks' => 5]);
  $this->exam->update(['status' => \App\Enums\ExamStatus::Ended]);

  actingAs($this->admin)
    ->postJson(
      instRoute(
        'exam-courseables.evaluate-theory',
        [$this->exam, $examCourseable],
        $this->institution
      ),
      ['scores' => [$question->id => 6]]
    )
    ->assertStatus(422)
    ->assertJsonValidationErrors(["scores.{$question->id}"]);
});

it('prevents unrelated teachers from evaluating theory answers', function () {
  $teacher = User::factory()
    ->teacher($this->institution)
    ->create();
  $courseable = CourseSession::factory()
    ->institution($this->institution)
    ->create();
  $examCourseable = ExamCourseable::factory()
    ->exam($this->exam)
    ->courseable($courseable)
    ->create([
      'theory_num_of_questions' => 1,
      'theory_evaluated' => false
    ]);
  $question = TheoryQuestion::factory()
    ->courseable($courseable)
    ->create(['marks' => 5]);
  $this->exam->update(['status' => \App\Enums\ExamStatus::Ended]);

  actingAs($teacher)
    ->postJson(
      instRoute(
        'exam-courseables.evaluate-theory',
        [$this->exam, $examCourseable],
        $this->institution
      ),
      ['scores' => [$question->id => 4]]
    )
    ->assertStatus(403);
});
