<?php

use App\Models\AcademicSession;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertSoftDeleted;

beforeEach(function () {
  $this->manager = User::factory()
    ->adminManager()
    ->create();
  $this->partnerManager = User::factory()
    ->partnerManager()
    ->create();
});

it('lists academic sessions for admin managers', function () {
  AcademicSession::factory()->create([
    'title' => '2024/2025',
    'order_index' => 10
  ]);
  AcademicSession::factory()->create([
    'title' => '2025/2026',
    'order_index' => 20
  ]);

  actingAs($this->manager)
    ->getJson(route('managers.academic-sessions.index'))
    ->assertOk()
    ->assertInertia(
      fn(Assert $page) => $page
        ->component('managers/academic-sessions/list-academic-sessions')
        ->has('academicSessions.data', 2)
        ->where('academicSessions.data.0.title', '2025/2026')
    );
});

it('stores an academic session', function () {
  actingAs($this->manager)
    ->postJson(route('managers.academic-sessions.store'), [
      'title' => '2026/2027',
      'order_index' => 30
    ])
    ->assertOk()
    ->assertJsonPath('academicSession.title', '2026/2027')
    ->assertJsonPath('academicSession.order_index', 30);

  assertDatabaseHas('academic_sessions', [
    'title' => '2026/2027',
    'order_index' => 30
  ]);
});

it('updates an academic session', function () {
  $academicSession = AcademicSession::factory()->create([
    'title' => '2026/2027',
    'order_index' => 30
  ]);

  actingAs($this->manager)
    ->putJson(route('managers.academic-sessions.update', [$academicSession]), [
      'title' => '2027/2028',
      'order_index' => 40
    ])
    ->assertOk()
    ->assertJsonPath('academicSession.title', '2027/2028')
    ->assertJsonPath('academicSession.order_index', 40);

  assertDatabaseHas('academic_sessions', [
    'id' => $academicSession->id,
    'title' => '2027/2028',
    'order_index' => 40
  ]);
});

it('validates duplicate academic session titles', function () {
  AcademicSession::factory()->create([
    'title' => '2026/2027',
    'order_index' => 30
  ]);

  actingAs($this->manager)
    ->postJson(route('managers.academic-sessions.store'), [
      'title' => '2026/2027',
      'order_index' => 31
    ])
    ->assertUnprocessable()
    ->assertJsonValidationErrors(['title']);
});

it('deletes an academic session', function () {
  $academicSession = AcademicSession::factory()->create([
    'title' => '2026/2027',
    'order_index' => 30
  ]);

  actingAs($this->manager)
    ->deleteJson(route('managers.academic-sessions.destroy', [$academicSession]))
    ->assertOk();

  assertSoftDeleted('academic_sessions', ['id' => $academicSession->id]);
});

it('prevents partner managers from managing academic sessions', function () {
  actingAs($this->partnerManager)
    ->postJson(route('managers.academic-sessions.store'), [
      'title' => '2026/2027',
      'order_index' => 30
    ])
    ->assertForbidden();
});
