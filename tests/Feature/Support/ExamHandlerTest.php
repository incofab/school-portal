<?php

use App\Enums\ExamStatus;
use App\Helpers\ExamAttemptFileHandler;
use App\Models\Event;
use App\Models\Exam;
use App\Models\ExamCourseable;
use App\Models\Institution;
use App\Models\Question;
use App\Models\Student;
use App\Support\ExamHandler;

use function PHPUnit\Framework\assertFalse;
use function PHPUnit\Framework\assertTrue;

beforeEach(function () {
  $this->institution = Institution::factory()->create();
  $this->event = Event::factory()
    ->institution($this->institution)
    ->started()
    ->eventCourseables(2)
    ->create();
  $this->eventCourseables = $this->event->eventCourseables;
  $this->student = Student::factory()
    ->withInstitution($this->institution)
    ->create();

  $this->exam = Exam::factory()
    ->started()
    ->examable($this->student)
    ->event($this->event)
    ->create(['num_of_questions' => 5]);
  $this->examCourseable = ExamCourseable::factory()
    ->exam($this->exam)
    ->courseable($this->eventCourseables->first()->courseable)
    ->create(['num_of_questions' => 5]);
  $this->examHandler = ExamHandler::make($this->exam);
  $this->examAttemptFileHandler = ExamAttemptFileHandler::make($this->exam);
  $this->filePath = $this->examAttemptFileHandler->getFullFilepath();
});

afterEach(function () {
  if (File::exists($this->filePath)) {
    File::delete($this->filePath);
  }
});

it('ensures that exam can start only when conditions are met', function () {
  $notStartedEventExam = Exam::factory()
    ->started()
    ->event(
      Event::factory()
        ->notStarted()
        ->create()
    )
    ->create();
  $startedEventExam = Exam::factory()
    ->started()
    ->event(
      Event::factory()
        ->started()
        ->create()
    )
    ->create();
  $endedEventExam = Exam::factory()
    ->started()
    ->event(
      Event::factory()
        ->ended()
        ->create()
    )
    ->create();

  $startedExam = Exam::factory()
    ->started()
    ->event($this->event)
    ->create();

  $pausedExam = Exam::factory()
    ->paused()
    ->event($this->event)
    ->create();
  $endedExam = Exam::factory()
    ->ended()
    ->event($this->event)
    ->create();

  assertTrue(ExamHandler::make($startedExam)->canRun());
  assertTrue(ExamHandler::make($pausedExam)->canRun());
  assertFalse(ExamHandler::make($endedExam)->canRun());

  assertTrue(ExamHandler::make($startedEventExam)->canRun());
  assertFalse(ExamHandler::make($notStartedEventExam)->canRun());
  assertFalse(ExamHandler::make($endedEventExam)->canRun());
});

it('can end an exam', function () {
  $this->exam->update([
    'status' => ExamStatus::Active,
    'end_time' => now()->addMinutes(10)
  ]);
  $questions = Question::factory(2)
    ->courseable($this->examCourseable->courseable)
    ->create();
  $studentAttempts = $questions
    ->mapWithKeys(fn($item) => [$item->id => $item->answer])
    ->toArray();
  $this->examAttemptFileHandler->syncExamFile();
  $this->examAttemptFileHandler->attemptQuestion($studentAttempts);
  $this->examHandler->endExam();

  expect($this->exam->status)->toBe(ExamStatus::Ended);
  expect($this->exam->pause_time)->toBeNull();
  expect($this->exam->end_time)->toBeNull();
  expect($this->exam->time_remaining)->toBe(floatval(0));
  expect($this->exam->score)->toBe(floatval(2));
  expect($this->exam->num_of_questions)->toBe(2);
  expect($this->exam->attempts->toArray())->toEqual($studentAttempts);
});

it('can re-evaluate an already ended exam', function () {
  $this->exam->update(['status' => ExamStatus::Ended]);
  $this->examHandler->endExam(true);

  expect($this->exam->status)->toBe(ExamStatus::Ended);
  expect($this->exam->pause_time)->toBeNull();
  expect($this->exam->end_time)->toBeNull();
  expect($this->exam->time_remaining)->toBe(floatval(0));
});
