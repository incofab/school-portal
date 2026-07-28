<?php

use App\Models\ClassificationGroup;
use App\Models\Institution;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;

beforeEach(function () {
  $this->institution = Institution::factory()->create();
  $this->admin = $this->institution->createdBy;
});

it('creates classification groups with default titles', function () {
  actingAs($this->admin)
    ->post(
      route('institutions.classification-groups.store', [
        $this->institution->uuid
      ]),
      [
        'title' => 'Junior School'
      ]
    )
    ->assertOk();

  assertDatabaseHas('classification_groups', [
    'institution_id' => $this->institution->id,
    'title' => 'Junior School',
    'head_of_school_title' => ClassificationGroup::DEFAULT_HEAD_OF_SCHOOL_TITLE,
    'head_of_class_title' => ClassificationGroup::DEFAULT_HEAD_OF_CLASS_TITLE,
    'student_title' => ClassificationGroup::DEFAULT_STUDENT_TITLE
  ]);
});

it('stores and updates custom classification group titles', function () {
  actingAs($this->admin)
    ->post(
      route('institutions.classification-groups.store', [
        $this->institution->uuid
      ]),
      [
        'title' => 'Primary School',
        'head_of_school_title' => 'Head Teacher',
        'head_of_class_title' => 'Class Teacher',
        'student_title' => 'Pupils'
      ]
    )
    ->assertOk();

  $classificationGroup = ClassificationGroup::query()
    ->where('title', 'Primary School')
    ->firstOrFail();

  actingAs($this->admin)
    ->put(
      route('institutions.classification-groups.update', [
        $this->institution->uuid,
        $classificationGroup
      ]),
      [
        'title' => 'Primary School',
        'head_of_school_title' => 'Proprietor',
        'head_of_class_title' => 'Tutor',
        'student_title' => 'Learners'
      ]
    )
    ->assertOk();

  assertDatabaseHas('classification_groups', [
    'id' => $classificationGroup->id,
    'head_of_school_title' => 'Proprietor',
    'head_of_class_title' => 'Tutor',
    'student_title' => 'Learners'
  ]);
});

it(
  'returns configured titles when searching classification groups',
  function () {
    $classificationGroup = ClassificationGroup::factory()
      ->for($this->institution)
      ->create([
        'title' => 'Nursery',
        'head_of_school_title' => 'Proprietor',
        'head_of_class_title' => 'Tutor',
        'student_title' => 'Learners'
      ]);

    actingAs($this->admin)
      ->getJson(
        route('institutions.classification-groups.search', [
          $this->institution->uuid,
          'search' => 'Nursery'
        ])
      )
      ->assertOk()
      ->assertJsonPath('result.0.id', $classificationGroup->id)
      ->assertJsonPath('result.0.head_of_school_title', 'Proprietor')
      ->assertJsonPath('result.0.head_of_class_title', 'Tutor')
      ->assertJsonPath('result.0.student_title', 'Learners');
  }
);
