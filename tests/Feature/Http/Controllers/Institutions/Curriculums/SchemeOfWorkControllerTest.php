<?php

use App\Enums\TermType;
use App\Models\Institution;
use App\Models\SchemeOfWork;
use App\Models\Topic;
use App\Models\InstitutionUser;
use App\Models\Student;
use App\Models\LessonPlan;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertSoftDeleted;
use function PHPUnit\Framework\assertEquals;

/**
 * ./vendor/bin/pest --filter SchemeOfWorkControllerTest
 */

beforeEach(function () {
  $this->institution = Institution::factory()->create();
  $this->institutionUser = InstitutionUser::factory()
    ->withInstitution($this->institution)
    ->create();
  $this->admin = $this->institution->createdBy;
  $this->topic = Topic::factory()
    ->for($this->institution)
    ->create();

  $this->student = Student::factory()
    ->withInstitution($this->institution)
    ->create();
});

it('tests the create page', function () {
  $route = route('institutions.scheme-of-works.create', [
    'institution' => $this->institution->uuid,
    'topic' => $this->topic->id
  ]);

  actingAs($this->student->user)
    ->getJson($route)
    ->assertForbidden();

  actingAs($this->admin)
    ->getJson($route)
    ->assertOk()
    ->assertInertia(function (AssertableInertia $assert) {
      return $assert
        ->has('topicId')
        ->where('topicId', $this->topic->id)
        ->component('institutions/scheme-of-works/create-edit-scheme-of-work');
    });
});

it('tests the edit page', function () {
  $schemeOfWork = SchemeOfWork::factory()
    ->topic($this->topic)
    ->create();

  $route = route('institutions.scheme-of-works.edit', [
    'institution' => $this->institution->uuid,
    'scheme_of_work' => $schemeOfWork->id
  ]);

  actingAs($this->admin)
    ->getJson($route)
    ->assertOk()
    ->assertInertia(function (AssertableInertia $assert) use ($schemeOfWork) {
      return $assert
        ->has('parentTopics')
        ->has('schemeOfWork')
        ->where('schemeOfWork.id', $schemeOfWork->id)
        ->component('institutions/scheme-of-works/create-edit-scheme-of-work');
    });
});

it('stores scheme of work data', function () {
  $route = route('institutions.scheme-of-works.store', [
    'institution' => $this->institution->uuid
  ]);

  $schemeOfWork = SchemeOfWork::factory()
    ->topic($this->topic)
    ->make()
    ->toArray();

  $schemeOfWorkData = [
    ...$schemeOfWork,
    'is_used_by_institution_group' => false
  ];

  actingAs($this->admin)
    ->postJson($route, [])
    ->assertJsonValidationErrors([
      'term',
      'topic_id',
      'week_number',
      'is_used_by_institution_group'
    ]);

  actingAs($this->admin)
    ->postJson($route, $schemeOfWorkData)
    ->assertOk();

  assertDatabaseCount('scheme_of_works', 1);
  assertDatabaseHas('scheme_of_works', $schemeOfWork);
});

it('updates scheme of work data', function () {
  $schemeOfWork = SchemeOfWork::factory()
    ->topic($this->topic)
    ->create();

  $route = route('institutions.scheme-of-works.update', [
    'institution' => $this->institution->uuid,
    'scheme_of_work' => $schemeOfWork->id
  ]);

  $updatedData = [
    'term' => TermType::First->value,
    'topic_id' => $this->topic->id,
    'week_number' => 2,
    'learning_objectives' => 'Updated objectives',
    'resources' => 'Updated resources',
    'is_used_by_institution_group' => true
  ];

  actingAs($this->admin)
    ->putJson($route, $updatedData)
    ->assertOk();

  $schemeOfWork->refresh();
  assertEquals($updatedData['term'], $schemeOfWork->term->value);
  assertEquals($updatedData['week_number'], $schemeOfWork->week_number);
  assertEquals(
    $updatedData['learning_objectives'],
    $schemeOfWork->learning_objectives
  );
});

it('deletes a scheme of work', function () {
  $schemeOfWork = SchemeOfWork::factory()
    ->topic($this->topic)
    ->create();

  $route = route('institutions.scheme-of-works.destroy', [
    'institution' => $this->institution->uuid,
    'scheme_of_work' => $schemeOfWork->id
  ]);

  actingAs($this->admin)
    ->deleteJson($route)
    ->assertOk();

  assertSoftDeleted('scheme_of_works', ['id' => $schemeOfWork->id]);
});

it('cannot delete a scheme of work with lesson plans', function () {
  $schemeOfWork = SchemeOfWork::factory()
    ->topic($this->topic)
    ->create();

  LessonPlan::factory()->create(['scheme_of_work_id' => $schemeOfWork->id]);

  $route = route('institutions.scheme-of-works.destroy', [
    'institution' => $this->institution->uuid,
    'scheme_of_work' => $schemeOfWork->id
  ]);

  actingAs($this->admin)
    ->deleteJson($route)
    ->assertStatus(403)
    ->assertJson([
      'message' => 'This Scheme-of-Work already has a Lesson-Plan.'
    ]);

  $this->assertDatabaseHas('scheme_of_works', ['id' => $schemeOfWork->id]);
});

it('restricts non-admin access to scheme of work routes', function () {
  $user = User::factory()
    ->teacher($this->institution)
    ->create();
  $schemeOfWork = SchemeOfWork::factory()
    ->topic($this->topic)
    ->create();

  $routes = [
    'create' => route('institutions.scheme-of-works.create', [
      'institution' => $this->institution->uuid,
      'topic' => $this->topic->id
    ]),
    'edit' => route('institutions.scheme-of-works.edit', [
      'institution' => $this->institution->uuid,
      'scheme_of_work' => $schemeOfWork->id
    ]),
    'store' => route('institutions.scheme-of-works.store', [
      'institution' => $this->institution->uuid
    ]),
    'update' => route('institutions.scheme-of-works.update', [
      'institution' => $this->institution->uuid,
      'scheme_of_work' => $schemeOfWork->id
    ]),
    'destroy' => route('institutions.scheme-of-works.destroy', [
      'institution' => $this->institution->uuid,
      'scheme_of_work' => $schemeOfWork->id
    ])
  ];

  foreach ($routes as $name => $route) {
    $method = match ($name) {
      'create', 'edit' => 'getJson',
      'store' => 'postJson',
      'update' => 'putJson',
      'destroy' => 'deleteJson'
    };

    actingAs($user)
      ->$method(
        $route,
        $name === 'store' || $name === 'update' ? ['term' => 'Test'] : []
      )
      ->assertForbidden();
  }
});
