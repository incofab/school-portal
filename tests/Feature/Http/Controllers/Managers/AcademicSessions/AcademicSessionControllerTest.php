<?php

use App\Models\AcademicSession;
use App\Support\SettingsHandler;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertSoftDeleted;

beforeEach(function () {
  SettingsHandler::clear();
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
      'order_index' => 30,
      'is_active' => false
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
      'order_index' => 40,
      'is_active' => false
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

it('stores an active academic session and deactivates the rest', function () {
  $oldActive = AcademicSession::factory()
    ->active()
    ->create(['title' => '2025/2026']);

  actingAs($this->manager)
    ->postJson(route('managers.academic-sessions.store'), [
      'title' => '2026/2027',
      'order_index' => 30,
      'is_active' => true
    ])
    ->assertOk()
    ->assertJsonPath('academicSession.is_active', true);

  assertDatabaseHas('academic_sessions', [
    'id' => $oldActive->id,
    'is_active' => false
  ]);
  assertDatabaseHas('academic_sessions', [
    'title' => '2026/2027',
    'is_active' => true
  ]);
});

it('updates an active academic session and deactivates the rest', function () {
  $oldActive = AcademicSession::factory()
    ->active()
    ->create(['title' => '2025/2026']);
  $academicSession = AcademicSession::factory()->create([
    'title' => '2026/2027',
    'order_index' => 30
  ]);

  actingAs($this->manager)
    ->putJson(route('managers.academic-sessions.update', [$academicSession]), [
      'title' => '2026/2027',
      'order_index' => 30,
      'is_active' => true
    ])
    ->assertOk()
    ->assertJsonPath('academicSession.is_active', true);

  assertDatabaseHas('academic_sessions', [
    'id' => $oldActive->id,
    'is_active' => false
  ]);
  assertDatabaseHas('academic_sessions', [
    'id' => $academicSession->id,
    'is_active' => true
  ]);
});

it('activates an academic session from the list action', function () {
  $oldActive = AcademicSession::factory()
    ->active()
    ->create(['title' => '2025/2026']);
  $academicSession = AcademicSession::factory()->create([
    'title' => '2026/2027'
  ]);

  actingAs($this->manager)
    ->postJson(route('managers.academic-sessions.activate', [$academicSession]))
    ->assertOk()
    ->assertJsonPath('academicSession.is_active', true);

  assertDatabaseHas('academic_sessions', [
    'id' => $oldActive->id,
    'is_active' => false
  ]);
  assertDatabaseHas('academic_sessions', [
    'id' => $academicSession->id,
    'is_active' => true
  ]);
});

it(
  'uses the active academic session as the default settings session',
  function () {
    AcademicSession::factory()->create(['title' => '2027/2028']);
    $activeSession = AcademicSession::factory()
      ->active()
      ->create(['title' => '2025/2026']);

    expect(SettingsHandler::make([])->getCurrentAcademicSession())->toBe(
      $activeSession->id
    );
  }
);

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
    ->deleteJson(
      route('managers.academic-sessions.destroy', [$academicSession])
    )
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
