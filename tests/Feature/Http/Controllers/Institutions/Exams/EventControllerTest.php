<?php
use App\Enums\InstitutionUserType;
use App\Models\Event;
use App\Models\Institution;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

beforeEach(function () {
  $this->institution = Institution::factory()->create();
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
    ->get(instRoute('events.index', [], $this->institution))
    ->assertStatus(403);

  // Attempt to access the controller as an admin
  actingAs($this->admin)
    ->get(instRoute('events.index', [], $this->institution))
    ->assertStatus(200);
});

it('displays the create event form', function () {
  actingAs($this->admin)
    ->get(instRoute('events.create', [], $this->institution))
    ->assertStatus(200);
});

it('deletes an event', function () {
  $event = Event::factory()
    ->institution($this->institution)
    ->create();
  // Create an event (if needed)
  actingAs($this->admin)
    ->delete(
      instRoute(
        'events.destroy',
        [
          'event' => $event
        ],
        $this->institution
      )
    )
    ->assertStatus(200);
  assertDatabaseMissing('events', ['id' => $event->id]);
});

it('stores a new event', function () {
  $data = Event::factory()
    ->institution($this->institution)
    ->make()
    ->toArray();

  actingAs($this->admin)
    ->post(instRoute('events.store', [], $this->institution), $data)
    ->assertStatus(200);
  assertDatabaseHas('events', [...$data, 'duration' => $data['duration'] * 60]);
});

it('updates an event', function () {
  $event = Event::factory()
    ->institution($this->institution)
    ->create();

  $data = Event::factory()
    ->institution($this->institution)
    ->make()
    ->toArray();

  actingAs($this->admin)
    ->put(
      instRoute(
        'events.update',
        [
          'event' => $event
        ],
        $this->institution
      ),
      $data
    )
    ->assertStatus(200);
  assertDatabaseHas('events', [...$data, 'duration' => $data['duration'] * 60]);
});
