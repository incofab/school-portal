<?php

namespace Tests\Feature\Http\Controllers\Institutions\Staff;

use App\Enums\FullTermType;
use App\Enums\TermType;
use App\Models\Assessment;
use App\Models\ClassDivision;
use App\Models\Institution;
use App\Support\MorphMap;

use function Pest\Laravel\{actingAs, assertDatabaseHas, assertSoftDeleted};

beforeEach(function () {
  $this->institution = Institution::factory()->create();
  $this->admin = $this->institution->createdBy;
  $this->classDivision = ClassDivision::factory()
    ->withInstitution($this->institution)
    ->create();
});

it('can list assessments', function () {
  $existingCount = $this->institution->assessments()->count();
  Assessment::factory()
    ->withInstitution($this->institution)
    ->count(5)
    ->create();

  actingAs($this->admin)
    ->get(route('institutions.assessments.index', $this->institution))
    ->assertOk()
    ->assertInertia(
      fn($assert) => $assert
        ->component('institutions/assessments/create-edit-assessment')
        ->has('assessments', $existingCount + 5)
    );
});

it('can create an assessment', function () {
  $title = 'New Assessment';
  actingAs($this->admin)
    ->post(route('institutions.assessments.store', $this->institution), [
      'term' => TermType::First->value,
      'for_mid_term' => true,
      'title' => $title,
      'max' => 50,
      'description' => 'Some description',
      'class_division_ids' => [$this->classDivision->id]
    ])
    ->assertOk();

  assertDatabaseHas('assessments', [
    'institution_id' => $this->institution->id,
    'term' => TermType::First->value,
    'for_mid_term' => true,
    'title' => 'new_assessment',
    'max' => 50,
    'description' => 'Some description'
  ]);
  $assessment = Assessment::where('title', 'new_assessment')->first();
  assertDatabaseHas('class_division_mappings', [
    'mappable_id' => $assessment->id,
    'mappable_type' => $assessment->getMorphClass(),
    'class_division_id' => $this->classDivision->id
  ]);
});

it('can update an assessment', function () {
  $assessment = Assessment::factory()
    ->withInstitution($this->institution)
    ->create();

  actingAs($this->admin)
    ->put(
      route('institutions.assessments.update', [
        $this->institution,
        $assessment
      ]),
      [
        'term' => TermType::Second->value,
        'for_mid_term' => false,
        'title' => 'Updated Assessment',
        'max' => 70,
        'description' => 'Updated description',
        'class_division_ids' => [$this->classDivision->id]
      ]
    )
    ->assertOk();

  assertDatabaseHas('assessments', [
    'id' => $assessment->id,
    'term' => TermType::Second->value,
    'for_mid_term' => false,
    'title' => 'updated_assessment',
    'max' => 70,
    'description' => 'Updated description'
  ]);

  assertDatabaseHas('class_division_mappings', [
    'mappable_id' => $assessment->id,
    'mappable_type' => MorphMap::key(Assessment::class),
    'class_division_id' => $this->classDivision->id
  ]);
});

it('can delete an assessment', function () {
  $assessment = Assessment::factory()
    ->withInstitution($this->institution)
    ->create();

  actingAs($this->admin)
    ->delete(
      route('institutions.assessments.destroy', [
        $this->institution,
        $assessment
      ])
    )
    ->assertOk();

  assertSoftDeleted('assessments', [
    'id' => $assessment->id
  ]);
});

it('can set assessment dependency', function () {
  $assessment = Assessment::factory()
    ->withInstitution($this->institution)
    ->create();

  actingAs($this->admin)
    ->post(
      route('institutions.assessments.set-dependency', [
        $this->institution,
        $assessment
      ]),
      [
        'depends_on' => FullTermType::First->value
      ]
    )
    ->assertOk();

  assertDatabaseHas('assessments', [
    'id' => $assessment->id,
    'depends_on' => FullTermType::First->value
  ]);
});
