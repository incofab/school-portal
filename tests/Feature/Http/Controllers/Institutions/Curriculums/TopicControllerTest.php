<?php

use App\Models\Institution;
use App\Models\User;
use App\Models\Topic;
use App\Models\Course;
use App\Models\InstitutionUser;
use App\Models\Student;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\postJson;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertNotNull;

beforeEach(function () {
  $this->institution = Institution::factory()->create();
  $this->institutionUser = InstitutionUser::factory()->withInstitution($this->institution)->create();
  $this->admin = $this->institution->createdBy;
  $this->user = $this->institutionUser->user;
  $this->course = Course::factory()->for($this->institution)->create();

  $this->student = Student::factory()
  ->withInstitution($this->institution)
  ->create();
});

/*
it('tests the index page', function () {

  $route = route('institutions.inst-topics.index', $this->institution);

  actingAs($this->student->user)
    ->getJson($route)
    ->assertForbidden();
    
  actingAs($this->admin)
    ->getJson($route)
    ->assertStatus(200);
});


it('tests the index page', function () {
  $route = route('institutions.inst-topics.index', [
    'institution' => $this->institution->uuid
  ]);

  Topic::factory(2)
    ->course($this->course)
    ->create();

  actingAs($this->student->user)
    ->getJson($route)
    ->assertForbidden();

  actingAs($this->admin)
    ->getJson($route)
    ->assertOk()
    ->assertInertia(
      function (AssertableInertia $assert){
        return $assert
        ->has('topics.data', 2)
        ->component('institutions/topics/list-topics');
      }
    );
});
*/


it('stores topic data', function () {
  $route = route('institutions.inst-topics.store', [
    'institution' => $this->institution->uuid
  ]);

  postJson($route, [])->assertJsonValidationErrors([
    'title',
    'description',
    'course_id'
  ]);

  $topicData = Topic::factory()
    ->for($this->course)
    ->make()
    ->toArray();

  postJson($route, $topicData)->assertOk();

  assertDatabaseCount('topics', 1);
  assertDatabaseHas('topics', $topicData);
});
/*
it('updates topic data', function () {
  $topic = Topic::factory()
    ->for($this->course)
    ->create();

  $route = route('institutions.inst-topics.update', [
    'institution' => $this->institution->uuid,
    'topic' => $topic->id
  ]);

  $updatedData = [
    'title' => 'Updated Topic Title',
    'description' => 'Updated topic description',
    'course_id' => $this->course->id
  ];

  actingAs($this->admin)
    ->putJson($route, $updatedData)
    ->assertOk();

  $topic->refresh();
  assertEquals($updatedData['title'], $topic->title);
  assertEquals($updatedData['description'], $topic->description);
});

it('deletes a topic', function () {
  $topic = Topic::factory()
    ->for($this->course)
    ->create();

  $route = route('institutions.inst-topics.destroy', [
    'institution' => $this->institution->uuid,
    'topic' => $topic->id
  ]);

  actingAs($this->admin)
    ->deleteJson($route)
    ->assertOk();

  assertDatabaseCount('topics', 0);
});

it('shows topic details', function () {
  $topic = Topic::factory()
    ->for($this->course)
    ->create();

  $route = route('institutions.inst-topics.show', [
    'institution' => $this->institution->uuid,
    'topic' => $topic->id
  ]);

  actingAs($this->admin)
    ->getJson($route)
    ->assertOk()
    ->assertInertia(
      fn(AssertableInertia $assert) => $assert
        ->has('topic')
        ->where('topic.id', $topic->id)
        ->where('topic.title', $topic->title)
        ->component('institutions/courses/topics/show')
    );
});
*/