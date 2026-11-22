<?php

use App\Enums\InstitutionUserStatus;
use App\Models\Classification;
use App\Models\ClassificationGroup;
use App\Models\Institution;
use App\Models\Student;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;

beforeEach(function () {
  $this->institution = Institution::factory()->create();
  $this->instAdmin = $this->institution->createdBy;
  $this->classification = Classification::factory()
    ->withInstitution($this->institution)
    ->create();
  $this->otherClassification = Classification::factory()
    ->withInstitution($this->institution)
    ->create();

  $this->route = fn(Classification $classification) => route(
    'institutions.classifications.student-status',
    [$this->institution->uuid, $classification]
  );
});

it('suspends all students in a class in one operation', function () {
  [$studentA, $studentB] = Student::factory(2)
    ->withInstitution($this->institution, $this->classification)
    ->create();
  $studentInOtherClass = Student::factory()
    ->withInstitution($this->institution, $this->otherClassification)
    ->create();

  actingAs($this->instAdmin)
    ->postJson(($this->route)($this->classification), [
      'status' => InstitutionUserStatus::Suspended->value,
      'status_message' => 'Class-wide disciplinary review'
    ])
    ->assertOk()
    ->assertJsonFragment([
      'message' => '2 student(s) updated successfully'
    ]);

  foreach ([$studentA, $studentB] as $student) {
    assertDatabaseHas('institution_users', [
      'id' => $student->institution_user_id,
      'status' => InstitutionUserStatus::Suspended->value,
      'status_message' => 'Class-wide disciplinary review'
    ]);
  }

  assertDatabaseHas('institution_users', [
    'id' => $studentInOtherClass->institution_user_id,
    'status' => InstitutionUserStatus::Active->value
  ]);
});

it(
  'unsuspends all students in a class and clears suspension messages',
  function () {
    [$studentA, $studentB] = Student::factory(2)
      ->withInstitution($this->institution, $this->classification)
      ->create();

    foreach ([$studentA, $studentB] as $student) {
      $student->institutionUser
        ->fill([
          'status' => InstitutionUserStatus::Suspended->value,
          'status_message' => 'Old message'
        ])
        ->save();
    }

    actingAs($this->instAdmin)
      ->postJson(($this->route)($this->classification), [
        'status' => InstitutionUserStatus::Active->value
      ])
      ->assertOk()
      ->assertJsonFragment([
        'message' => '2 student(s) updated successfully'
      ]);

    foreach ([$studentA, $studentB] as $student) {
      assertDatabaseHas('institution_users', [
        'id' => $student->institution_user_id,
        'status' => InstitutionUserStatus::Active->value,
        'status_message' => null
      ]);
    }
  }
);

it(
  'prevents non-admin users from bulk updating class student status',
  function () {
    $teacher = User::factory()
      ->teacher($this->institution)
      ->create();
    $student = Student::factory()
      ->withInstitution($this->institution, $this->classification)
      ->create();

    actingAs($teacher)
      ->postJson(($this->route)($this->classification), [
        'status' => InstitutionUserStatus::Suspended->value,
        'status_message' => 'Should not apply'
      ])
      ->assertForbidden();

    assertDatabaseHas('institution_users', [
      'id' => $student->institution_user_id,
      'status' => InstitutionUserStatus::Active->value
    ]);
  }
);

it(
  'stores optional class order and sorts classifications by order then title',
  function () {
    $group = ClassificationGroup::factory()
      ->for($this->institution)
      ->create();
    $second = Classification::factory()
      ->classificationGroup($group)
      ->create(['title' => 'Zeta', 'order' => 3]);
    $firstByTitle = Classification::factory()
      ->classificationGroup($group)
      ->create(['title' => 'Alpha', 'order' => 3]);
    Classification::factory()
      ->classificationGroup($group)
      ->create(['title' => 'Highest', 'order' => 7]);

    actingAs($this->instAdmin)
      ->getJson(
        route('institutions.classifications.search', [$this->institution->uuid])
      )
      ->assertOk()
      ->assertJsonPath('result.0.title', 'Highest')
      ->assertJsonPath('result.1.id', $firstByTitle->id)
      ->assertJsonPath('result.2.id', $second->id);

    actingAs($this->instAdmin)
      ->post(
        route('institutions.classifications.store', [$this->institution->uuid]),
        [
          'title' => 'Ordered Class',
          'order' => 9,
          'classification_group_id' => $group->id
        ]
      )
      ->assertOk();

    assertDatabaseHas('classifications', [
      'institution_id' => $this->institution->id,
      'title' => 'Ordered Class',
      'order' => 9
    ]);

    $orderedClassification = Classification::query()
      ->where('title', 'Ordered Class')
      ->firstOrFail();

    actingAs($this->instAdmin)
      ->put(
        route('institutions.classifications.update', [
          $this->institution->uuid,
          $orderedClassification
        ]),
        [
          'title' => 'Ordered Class',
          'order' => 12,
          'classification_group_id' => $group->id
        ]
      )
      ->assertOk();

    assertDatabaseHas('classifications', [
      'id' => $orderedClassification->id,
      'order' => 12
    ]);
  }
);
