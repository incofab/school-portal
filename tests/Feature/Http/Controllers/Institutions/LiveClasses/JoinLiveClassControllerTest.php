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

use function Pest\Laravel\actingAs;

/**
 * ./vendor/bin/pest --filter JoinLiveClassControllerTest
 */

beforeEach(function () {
  $this->institution = Institution::factory()->create();

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
      'teacher_user_id' => User::factory()->create()->id,
      'title' => 'Live class',
      'meet_url' => 'https://meet.example.com/live-class',
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

it('redirects a student to the live class meeting when allowed', function () {
  $liveClass = ($this->createLiveClass)([
    'meet_url' => 'https://meet.example.com/joinable'
  ]);

  actingAs($this->student)
    ->getJson(
      route('institutions.live-classes.join', [$this->institution, $liveClass])
    )
    ->assertRedirect($liveClass->meet_url);
});

it('prevents joining an inactive live class', function () {
  $liveClass = ($this->createLiveClass)(['is_active' => false]);

  actingAs($this->student)
    ->get(
      route('institutions.live-classes.join', [$this->institution, $liveClass])
    )
    ->assertStatus(403);
});

it('prevents joining a live class outside the student class', function () {
  $otherClassification = Classification::factory()
    ->withInstitution($this->institution)
    ->create();
  $liveClass = ($this->createLiveClass)([
    'liveable_type' => $otherClassification->getMorphClass(),
    'liveable_id' => $otherClassification->id
  ]);

  actingAs($this->student)
    ->get(
      route('institutions.live-classes.join', [$this->institution, $liveClass])
    )
    ->assertStatus(403);
});

it(
  'returns 404 when the live class does not belong to the institution',
  function () {
    $otherInstitution = Institution::factory()->create();
    $otherClassification = Classification::factory()
      ->withInstitution($otherInstitution)
      ->create();
    $liveClass = LiveClass::query()->create([
      'institution_id' => $otherInstitution->id,
      'teacher_user_id' => User::factory()->create()->id,
      'title' => 'Other live class',
      'meet_url' => 'https://meet.example.com/other',
      'liveable_type' => $otherClassification->getMorphClass(),
      'liveable_id' => $otherClassification->id,
      'starts_at' => now()
        ->addHour()
        ->toDateTimeString(),
      'ends_at' => now()
        ->addHours(2)
        ->toDateTimeString(),
      'is_active' => true
    ]);

    actingAs($this->student)
      ->get(
        route('institutions.live-classes.join', [
          $this->institution,
          $liveClass
        ])
      )
      ->assertNotFound();
  }
);
