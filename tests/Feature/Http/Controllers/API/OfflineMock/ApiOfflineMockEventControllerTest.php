<?php

namespace Tests\Feature\API\OfflineMock;

use App\Actions\OfflineMock\MockEventHandler;
use App\Models\Event;
use App\Models\Institution;
use App\Models\Instruction;
use App\Models\Passage;
use App\Models\Question;

use function Pest\Laravel\getJson;

beforeEach(function () {
  $this->institution = Institution::factory()->create();
  $this->handler = MockEventHandler::make();
});

it(
  'returns a list of formatted events for the institution, ordered by latest ID first',
  function () {
    $event1 = Event::factory()
      ->institution($this->institution)
      ->eventCourseables(1)
      ->create(['created_at' => now()->subHour()]);
    $event2 = Event::factory()
      ->institution($this->institution)
      ->eventCourseables(1)
      ->create(['created_at' => now()]); // Newer, higher ID

    // Eager load relations similar to controller's 'with' and MockEventHandler's needs for accurate comparison
    $expectedEvent1Data = $this->handler->formatEvent(
      $event1->fresh(['eventCourseables.courseable.course'])
    );
    $expectedEvent2Data = $this->handler->formatEvent(
      $event2->fresh(['eventCourseables.courseable.course'])
    );

    getJson(
      route('offline-mock.events.index', [
        'institution' => $this->institution->code
      ])
    )
      ->assertOk()
      ->assertJsonCount(2, 'data');
  }
);

it('returns a single formatted event', function () {
  $event = Event::factory()
    ->institution($this->institution)
    ->eventCourseables(1)
    ->create();

  $expectedData = $this->handler->formatEvent(
    $event->fresh(['eventCourseables.courseable.course']),
    false
  );

  $response = getJson(
    route('offline-mock.events.show', [
      'institution' => $this->institution->code,
      'event' => $event->id
    ])
  )
    ->assertOk()
    ->assertJson([
      'data' => collect($expectedData)
        ->except('starts_at', 'status')
        ->toArray()
    ]);
});

it(
  'returns a single formatted event with detailed questions, passages, and instructions',
  function () {
    $event = Event::factory()
      ->institution($this->institution)
      ->eventCourseables(1)
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

    // Prepare expected data
    // The controller loads various relations. MockEventHandler will use them if present or fetch.
    $reloadedEvent = $event->fresh([
      'eventCourseables.courseable.course',
      'eventCourseables.courseable.questions',
      'eventCourseables.courseable.passages',
      'eventCourseables.courseable.instructions'
    ]);
    $expectedData = $this->handler->formatEvent($reloadedEvent, true);

    $response = $this->getJson(
      route('offline-mock.events.deep-show', [
        'institution' => $this->institution->code,
        'event' => $event->id
      ])
    )
      ->assertOk()
      ->assertJson([
        'data' => collect($expectedData)
          ->except('event_courses', 'status', 'starts_at')
          ->toArray()
      ]);
    // Also works with event code
    $response = $this->getJson(
      route('offline-mock.events.deep-show-by-code', [
        'institution' => $this->institution->code,
        'event' => $event->code
      ])
    )
      ->assertOk()
      ->assertJson([
        'data' => collect($expectedData)
          ->except('event_courses', 'status', 'starts_at')
          ->toArray()
      ]);
    // Additional checks for presence of detailed data
    $responseData = $response->json('data');
    expect(
      $responseData['event_courses'][0]['course_session']['questions']
    )->toHaveCount(2);
    expect(
      $responseData['event_courses'][0]['course_session']['passages']
    )->toHaveCount(2);
    expect(
      $responseData['event_courses'][0]['course_session']['instructions']
    )->toHaveCount(2);
  }
);
