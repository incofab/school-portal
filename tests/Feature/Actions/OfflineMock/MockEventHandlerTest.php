<?php

namespace Tests\Unit\Actions\OfflineMock;

use App\Actions\OfflineMock\MockEventHandler;
use App\Models\Event;
use App\Models\Institution;
use App\Models\Instruction;
use App\Models\Passage;
use App\Models\Question;

use function PHPUnit\Framework\assertEquals;

beforeEach(function () {
  $this->institution = Institution::factory()->create();
});

it(
  'formats event data correctly without questions, passages, and instructions',
  function () {
    $event = Event::factory()
      ->institution($this->institution)
      ->notStarted()
      ->eventCourseables()
      ->create();
    $eventCourseable = $event->eventCourseables()->first();
    $courseSession = $eventCourseable->courseable;
    $course = $courseSession->course;

    $handler = MockEventHandler::make();

    // Act
    $formattedEvent = $handler->formatEvent($event, false);

    // Assert
    expect($formattedEvent)->toBeArray();
    expect($formattedEvent)
      ->toHaveKey('id', $event->id)
      ->toHaveKey('code', $event->code)
      ->toHaveKey('title', $event->title)
      ->toHaveKey('description', $event->description)
      ->toHaveKey('starts_at') //, $event->starts_at->toISOString())
      ->toHaveKey('num_of_subjects', $event->num_of_subjects)
      ->toHaveKey('status', $event->status)
      ->toHaveKey('event_courses');

    assertEquals(
      $formattedEvent['starts_at']->toISOString(),
      $event->starts_at->toISOString()
    );

    expect($formattedEvent['event_courses'])
      ->toBeArray()
      ->toHaveCount(1);
    $formattedEventCourse = $formattedEvent['event_courses'][0];

    expect($formattedEventCourse)
      ->toHaveKey('id', $eventCourseable->id)
      ->toHaveKey('event_id', $event->id)
      ->toHaveKey('course_session_id', $courseSession->id)
      ->toHaveKey('course_session');

    $formattedCourseSession = $formattedEventCourse['course_session'];
    expect($formattedCourseSession)
      ->toHaveKey('id', $courseSession->id)
      ->toHaveKey('session', $courseSession->session)
      ->toHaveKey('course_id', $course->id)
      ->toHaveKey('category', $courseSession->category)
      ->toHaveKey('general_instructions', $courseSession->general_instructions)
      ->toHaveKey('course');

    expect($formattedCourseSession['course'])
      ->toHaveKey('id', $course->id)
      ->toHaveKey('course_code', $course->code)
      ->toHaveKey('course_title', $course->title);

    // Ensure question-related keys are not present
    expect($formattedCourseSession)
      ->not->toHaveKey('questions')
      ->not->toHaveKey('passages')
      ->not->toHaveKey('instructions');
  }
);

it(
  'formats event data correctly with questions, passages, and instructions',
  function () {
    $event = Event::factory()
      ->institution($this->institution)
      ->notStarted()
      ->eventCourseables()
      ->create();
    $eventCourseable = $event->eventCourseables()->first();
    $courseSession = $eventCourseable->courseable;
    // $course = $courseSession->course;
    Question::factory(2)
      ->courseable($courseSession)
      ->create();
    Passage::factory(2)
      ->courseable($courseSession)
      ->create();
    Instruction::factory(2)
      ->courseable($courseSession)
      ->create();

    $handler = MockEventHandler::make();

    // Act
    $formattedEvent = $handler->formatEvent($event, true);

    // Assert
    expect($formattedEvent)->toBeArray();
    expect($formattedEvent)->toHaveKey('id', $event->id); // Basic event checks
    expect($formattedEvent['event_courses'])
      ->toBeArray()
      ->toHaveCount(1);

    $formattedEventCourse = $formattedEvent['event_courses'][0];
    expect($formattedEventCourse)->toHaveKey('course_session');

    $formattedCourseSession = $formattedEventCourse['course_session'];
    expect($formattedCourseSession)
      ->toHaveKey('id', $courseSession->id)
      ->toHaveKey('course'); // Basic course session checks

    // Assert Questions
    expect($formattedCourseSession)->toHaveKey('questions');
    expect($formattedCourseSession['questions'])
      ->toBeArray()
      ->toHaveCount(2);
    $sampleQuestion = $courseSession->questions->first();
    $formattedQuestion = collect(
      $formattedCourseSession['questions']
    )->firstWhere('id', $sampleQuestion->id);
    expect($formattedQuestion)
      ->toHaveKey('id', $sampleQuestion->id)
      ->toHaveKey('question', $sampleQuestion->question)
      ->toHaveKey('question_no', $sampleQuestion->question_no)
      ->toHaveKey('option_a', $sampleQuestion->option_a)
      ->toHaveKey('option_b', $sampleQuestion->option_b)
      ->toHaveKey('option_c', $sampleQuestion->option_c)
      ->toHaveKey('option_d', $sampleQuestion->option_d)
      ->toHaveKey('option_e', $sampleQuestion->option_e)
      ->toHaveKey('answer', $sampleQuestion->answer)
      ->toHaveKey('answer_meta', $sampleQuestion->answer_meta)
      ->toHaveKey('course_session_id', $courseSession->id);

    // Assert Passages
    expect($formattedCourseSession)->toHaveKey('passages');
    expect($formattedCourseSession['passages'])
      ->toBeArray()
      ->toHaveCount(2);
    $samplePassage = $courseSession->passages->first();
    $formattedPassage = $formattedCourseSession['passages'][0];
    expect($formattedPassage)
      ->toHaveKey('id', $samplePassage->id)
      ->toHaveKey('passage', $samplePassage->passage)
      ->toHaveKey('from', $samplePassage->from)
      ->toHaveKey('to', $samplePassage->to)
      ->toHaveKey('course_session_id', $courseSession->id);

    // Assert Instructions
    expect($formattedCourseSession)->toHaveKey('instructions');
    expect($formattedCourseSession['instructions'])
      ->toBeArray()
      ->toHaveCount(2);
    $sampleInstruction = $courseSession->instructions->first();
    $formattedInstruction = $formattedCourseSession['instructions'][0];
    expect($formattedInstruction)
      ->toHaveKey('id', $sampleInstruction->id)
      ->toHaveKey('instruction', $sampleInstruction->instruction)
      ->toHaveKey('from', $sampleInstruction->from)
      ->toHaveKey('to', $sampleInstruction->to)
      ->toHaveKey('course_session_id', $courseSession->id);
  }
);
