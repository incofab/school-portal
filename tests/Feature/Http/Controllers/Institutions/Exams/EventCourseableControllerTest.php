<?php

use App\Models\CourseSession;
use App\Models\Event;
use App\Models\EventCourseable;
use App\Models\Institution;
use App\Models\User;

use function Pest\Laravel\actingAs;
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
  $this->nonAdminUser = User::factory()
    ->student($this->institution)
    ->create();
});

it('only allows admins to access the controller', function () {
  // Attempt to access the controller as a non-admin
  actingAs($this->nonAdminUser)
    ->getJson(
      instRoute('event-courseables.index', [$this->event], $this->institution)
    )
    ->assertStatus(403);

  // Attempt to access the controller as an admin
  actingAs($this->admin)
    ->getJson(
      instRoute('event-courseables.index', [$this->event], $this->institution)
    )
    ->assertStatus(200);
});

it('displays the create event-courseable form', function () {
  actingAs($this->admin)
    ->getJson(
      instRoute('event-courseables.create', [$this->event], $this->institution)
    )
    ->assertStatus(200);
});

it('deletes an event-courseable', function () {
  $eventCourseable = EventCourseable::factory()
    ->event($this->event)
    ->create();
  // Create an event (if needed)
  actingAs($this->admin)
    ->deleteJson(
      instRoute(
        'event-courseables.destroy',
        [$eventCourseable],
        $this->institution
      )
    )
    ->assertStatus(200);
  assertDatabaseMissing('event_courseables', ['id' => $eventCourseable->id]);
});

it('stores new event courseables', function () {
  $courseable = CourseSession::factory()
    ->institution($this->institution)
    ->create();
  $data = EventCourseable::factory()
    ->event($this->event)
    ->make(['courseable_id' => $courseable])
    ->toArray();
  actingAs($this->admin)
    ->postJson(
      instRoute('event-courseables.store', [$this->event], $this->institution),
      $data
    )
    ->assertStatus(200);
  assertDatabaseHas('event_courseables', $data);
});
