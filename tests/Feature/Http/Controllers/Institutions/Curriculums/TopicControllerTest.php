<?php

use App\Enums\TermType;
use App\Models\ClassificationGroup;
use App\Models\Institution;
use App\Models\User;
use App\Models\Topic;
use App\Models\Course;
use App\Models\CourseTeacher;
use App\Models\InstitutionUser;
use App\Models\SchemeOfWork;
use App\Models\Student;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertSoftDeleted;
use function Pest\Laravel\postJson;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertNotNull;

beforeEach(function () {
  $this->institution = Institution::factory()->create();
  $this->admin = $this->institution->createdBy;
  $this->course = Course::factory()
    ->withInstitution($this->institution)
    ->create();

  $this->student = Student::factory()
    ->withInstitution($this->institution)
    ->create();
  $this->classification = $this->student->classification;
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
    ->assertInertia(function (AssertableInertia $assert) {
      return $assert
        ->has('topics.data', 2)
        ->component('institutions/topics/list-topics');
    });
});

it('stores topic data', function () {
  $route = route('institutions.inst-topics.store-or-update', [
    'institution' => $this->institution->uuid
  ]);

  $courseTeacher = CourseTeacher::factory()
    ->withInstitution($this->institution)
    ->create([
      'course_id' => $this->course->id,
      'classification_id' => $this->classification->id
    ]);

  actingAs($this->admin)
    ->postJson($route, [])
    ->assertJsonValidationErrors(['title', 'description', 'course_id']);

  $topicData = Topic::factory()
    ->course($this->course)
    ->make()
    ->toArray();

  postJson($route, [
    ...$topicData,
    'term' => TermType::First->value,
    'week_number' => 1,
    'is_used_by_institution_group' => false,
    'user_id' => $courseTeacher->user_id,
    'classification_group_id' => $this->classification->classification_group_id
  ])->assertOk();

  assertDatabaseCount('topics', 1);
  assertDatabaseHas('topics', $topicData);
});

it('updates topic data', function () {
  $topic = Topic::factory()
    ->course($this->course)
    ->create();

  $route = route('institutions.inst-topics.store-or-update', [
    'institution' => $this->institution->uuid,
    'topic' => $topic->id
  ]);

  $updatedData = [
    'title' => 'Updated Topic Title',
    'description' => 'Updated topic description',
    'course_id' => $this->course->id,
    'is_used_by_institution_group' => false,
    'classification_group_id' => ClassificationGroup::factory()
      ->for($this->institution)
      ->create()->id
  ];

  actingAs($this->admin)
    ->postJson($route, $updatedData)
    ->assertOk();

  $topic->refresh();
  assertEquals($updatedData['title'], $topic->title);
  assertEquals($updatedData['description'], $topic->description);
});

it('deletes a topic', function () {
  [$topic1, $topic2] = Topic::factory(2)
    ->course($this->course)
    ->create();
  $topic3 = Topic::factory()
    ->course($this->course)
    ->parentTopic($topic2)
    ->create();

  SchemeOfWork::factory()
    ->topic($topic1)
    ->create();

  $route = route('institutions.inst-topics.destroy', [
    'institution' => $this->institution->uuid,
    'topic' => $topic1->id
  ]);

  actingAs($this->admin)
    ->deleteJson(
      route('institutions.inst-topics.destroy', [
        'institution' => $this->institution->uuid,
        'topic' => $topic1->id
      ])
    )
    ->assertForbidden();
  actingAs($this->admin)
    ->deleteJson(
      route('institutions.inst-topics.destroy', [
        'institution' => $this->institution->uuid,
        'topic' => $topic2->id
      ])
    )
    ->assertForbidden();

  actingAs($this->admin)
    ->deleteJson(
      route('institutions.inst-topics.destroy', [
        'institution' => $this->institution->uuid,
        'topic' => $topic3->id
      ])
    )
    ->assertOk();

  assertSoftDeleted('topics', ['id' => $topic3->id]);
});

it('shows topic details', function () {
  $topic = Topic::factory()
    ->course($this->course)
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
        ->component('institutions/topics/show-topic')
    );
});
