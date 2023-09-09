<?php

use App\Models\Event;
use App\Models\Exam;
use App\Support\ExamHandler;

use function PHPUnit\Framework\assertFalse;
use function PHPUnit\Framework\assertTrue;

beforeEach(function () {
  $this->event = Event::factory()
    ->started()
    ->create();
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
