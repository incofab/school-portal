<?php

use App\Models\CourseSession;
use App\Models\Event;
use App\Models\EventCourseable;
use App\Models\Exam;
use App\Models\Institution;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

beforeEach(function () {
  $this->institution = Institution::factory()->create();
  $this->event = Event::factory()
    ->institution($this->institution)
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
    ->getJson(instRoute('exams.index', [$this->event], $this->institution))
    ->assertStatus(403);

  // Attempt to access the controller as an admin
  actingAs($this->admin)
    ->getJson(instRoute('exams.index', [$this->event], $this->institution))
    ->assertStatus(200);
});

it('displays the create exam form', function () {
  actingAs($this->admin)
    ->getJson(instRoute('exams.create', [$this->event], $this->institution))
    ->assertStatus(200);
});

it('deletes an event', function () {
  $exam = Exam::factory()
    ->event($this->event)
    ->create();
  // Create an event (if needed)
  actingAs($this->admin)
    ->deleteJson(instRoute('exams.destroy', [$exam], $this->institution))
    ->assertStatus(200);
  assertDatabaseMissing('exams', ['id' => $exam->id]);
});

it('stores a new exam and exam courseables', function () {
  $eventCourseables = EventCourseable::factory()
    ->count(3)
    ->event($this->event)
    ->create();
  $data = Exam::factory()
    ->event($this->event)
    ->make()
    ->toArray();
  actingAs($this->admin)
    ->postJson(instRoute('exams.store', [$this->event], $this->institution), [
      ...$data,
      'courseables' => $eventCourseables
        ->map(
          fn($item) => [
            'courseable_id' => $item->courseable->id,
            'courseable_type' => $item->courseable->getMorphClass()
          ]
        )
        ->toArray()
    ])
    ->assertStatus(200);

  $exam = Exam::where(
    'external_reference',
    $data['external_reference']
  )->first();

  assertDatabaseHas(
    'exams',
    collect($data)
      ->only('event_id', 'external_reference')
      ->toArray()
  );

  $eventCourseables->map(
    fn($item) => assertDatabaseHas('exam_courseables', [
      'exam_id' => $exam->id,
      'courseable_type' => $item->courseable->getMorphClass(),
      'courseable_id' => $item->courseable->id
    ])
  );
});
