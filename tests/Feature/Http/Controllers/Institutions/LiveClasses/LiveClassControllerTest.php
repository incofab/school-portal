<?php

use App\Enums\InstitutionUserType;
use App\Models\ClassDivision;
use App\Models\Classification;
use App\Models\ClassificationGroup;
use App\Models\Institution;
use App\Models\InstitutionUser;
use App\Models\LiveClass;
use App\Models\Student;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

/**
 * ./vendor/bin/pest --filter LiveClassControllerTest
 */

beforeEach(function () {
  $this->institution = Institution::factory()->create();
  $this->admin = $this->institution->createdBy;

  $this->teacher = User::factory()->create();
  $this->teacherInstitutionUser = InstitutionUser::factory()
    ->withInstitution($this->institution)
    ->create([
      'user_id' => $this->teacher->id,
      'role' => InstitutionUserType::Teacher->value
    ]);

  $this->classificationGroup = ClassificationGroup::factory()
    ->withInstitution($this->institution)
    ->create();
  $this->classification = Classification::factory()
    ->classificationGroup($this->classificationGroup)
    ->create();
  $this->classDivision = ClassDivision::factory()
    ->withInstitution($this->institution)
    ->create();
  $this->classification->classDivisions()->attach($this->classDivision);

  $this->studentModel = Student::factory()
    ->withInstitution($this->institution, $this->classification)
    ->create();
  $this->student = $this->studentModel->user;

  $this->createLiveClass = function (array $overrides = []) {
    return LiveClass::query()->create([
      'institution_id' => $this->institution->id,
      'teacher_user_id' => $this->teacher->id,
      'title' => 'Physics class',
      'meet_url' => 'https://meet.example.com/physics',
      'liveable_type' => $this->classification->getMorphClass(),
      'liveable_id' => $this->classification->id,
      'starts_at' => now()
        ->addHour()
        ->toDateTimeString(),
      'ends_at' => now()
        ->addHours(2)
        ->toDateTimeString(),
      'is_active' => true,
      ...$overrides
    ]);
  };
});

it('renders the live classes index for admin', function () {
  ($this->createLiveClass)();
  ($this->createLiveClass)([
    'title' => 'Chemistry class',
    'meet_url' => 'https://meet.example.com/chemistry'
  ]);

  actingAs($this->admin)
    ->get(route('institutions.live-classes.index', $this->institution))
    ->assertOk()
    ->assertInertia(
      fn(AssertableInertia $assert) => $assert
        ->component('institutions/live-classes/list-live-classes')
        ->has('liveClasses', 2)
    );
});

it(
  'filters live classes for students by class associations and active status',
  function () {
    $classificationClass = ($this->createLiveClass)([
      'title' => 'Classification class',
      'meet_url' => 'https://meet.example.com/classification'
    ]);
    $groupClass = ($this->createLiveClass)([
      'title' => 'Group class',
      'meet_url' => 'https://meet.example.com/group',
      'liveable_type' => $this->classificationGroup->getMorphClass(),
      'liveable_id' => $this->classificationGroup->id
    ]);
    $divisionClass = ($this->createLiveClass)([
      'title' => 'Division class',
      'meet_url' => 'https://meet.example.com/division',
      'liveable_type' => $this->classDivision->getMorphClass(),
      'liveable_id' => $this->classDivision->id
    ]);
    ($this->createLiveClass)([
      'title' => 'Inactive class',
      'meet_url' => 'https://meet.example.com/inactive',
      'is_active' => false
    ]);
    $otherClassification = Classification::factory()
      ->withInstitution($this->institution)
      ->create();
    ($this->createLiveClass)([
      'title' => 'Other class',
      'meet_url' => 'https://meet.example.com/other',
      'liveable_type' => $otherClassification->getMorphClass(),
      'liveable_id' => $otherClassification->id
    ]);

    actingAs($this->student)
      ->get(route('institutions.live-classes.index', $this->institution))
      ->assertOk()
      ->assertInertia(
        fn(AssertableInertia $assert) => $assert
          ->component('institutions/live-classes/list-live-classes')
          ->has('liveClasses', 3)
          ->where('liveClasses', function ($liveClasses) use (
            $classificationClass,
            $groupClass,
            $divisionClass
          ) {
            $ids = collect($liveClasses)
              ->pluck('id')
              ->sort()
              ->values()
              ->all();
            $expected = collect([
              $classificationClass->id,
              $groupClass->id,
              $divisionClass->id
            ])
              ->sort()
              ->values()
              ->all();
            return $ids === $expected;
          })
      );
  }
);

it('renders the create live class page', function () {
  actingAs($this->teacher)
    ->get(route('institutions.live-classes.create', $this->institution))
    ->assertOk()
    ->assertInertia(
      fn(AssertableInertia $assert) => $assert->component(
        'institutions/live-classes/create-edit-live-class'
      )
    );
});

it('stores a live class', function () {
  $startsAt = now()
    ->addHour()
    ->toDateTimeString();
  $endsAt = now()
    ->addHours(2)
    ->toDateTimeString();
  $payload = [
    'title' => 'New live class',
    'meet_url' => 'https://meet.example.com/new-class',
    'liveable_type' => Classification::class,
    'liveable_id' => $this->classification->id,
    'starts_at' => $startsAt,
    'ends_at' => $endsAt,
    'is_active' => false
  ];

  actingAs($this->teacher)
    ->postJson(
      route('institutions.live-classes.store', $this->institution),
      $payload
    )
    ->assertOk()
    ->assertJson(['ok' => true]);

  assertDatabaseHas('live_classes', [
    'institution_id' => $this->institution->id,
    'teacher_user_id' => $this->teacher->id,
    'title' => $payload['title'],
    'meet_url' => $payload['meet_url'],
    'liveable_type' => $this->classification->getMorphClass(),
    'liveable_id' => $this->classification->id,
    'starts_at' => $startsAt,
    'ends_at' => $endsAt,
    'is_active' => false
  ]);
});

it('updates a live class', function () {
  $liveClass = ($this->createLiveClass)(['is_active' => false]);
  $startsAt = now()
    ->addDay()
    ->toDateTimeString();
  $endsAt = now()
    ->addDays(2)
    ->toDateTimeString();
  $payload = [
    'title' => 'Updated live class',
    'meet_url' => 'https://meet.example.com/updated-class',
    'liveable_type' => ClassificationGroup::class,
    'liveable_id' => $this->classificationGroup->id,
    'starts_at' => $startsAt,
    'ends_at' => $endsAt,
    'is_active' => true
  ];

  actingAs($this->teacher)
    ->putJson(
      route('institutions.live-classes.update', [
        $this->institution,
        $liveClass
      ]),
      $payload
    )
    ->assertOk();

  assertDatabaseHas('live_classes', [
    'id' => $liveClass->id,
    'title' => $payload['title'],
    'meet_url' => $payload['meet_url'],
    'liveable_type' => $this->classificationGroup->getMorphClass(),
    'liveable_id' => $this->classificationGroup->id,
    'starts_at' => $startsAt,
    'ends_at' => $endsAt,
    'is_active' => true
  ]);
});

it('deletes a live class', function () {
  $liveClass = ($this->createLiveClass)();

  actingAs($this->admin)
    ->deleteJson(
      route('institutions.live-classes.destroy', [
        $this->institution,
        $liveClass
      ])
    )
    ->assertOk();

  assertDatabaseMissing('live_classes', ['id' => $liveClass->id]);
});
