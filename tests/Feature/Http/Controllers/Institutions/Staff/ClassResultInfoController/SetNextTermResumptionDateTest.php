<?php

use App\Enums\TermType;
use App\Models\AcademicSession;
use App\Models\Classification;
use App\Models\ClassificationGroup;
use App\Models\ClassResultInfo;
use App\Models\Institution;

use function Pest\Laravel\actingAs;

beforeEach(function () {
  $this->institution = Institution::factory()->create();
  $this->instAdmin = $this->institution->createdBy;
  $this->academicSession = AcademicSession::factory()->create();
  $this->term = TermType::First->value;

  [$this->classGroup1, $classGroup2] = ClassificationGroup::factory(2)
    ->withInstitution($this->institution)
    ->create();
  [$classes1, $classes2] = Classification::factory(2)
    ->classificationGroup($this->classGroup1)
    ->create();
  $classForGroup2 = Classification::factory()
    ->classificationGroup($classGroup2)
    ->create();

  $this->requestData = [
    'academic_session_id' => $this->academicSession->id,
    'term' => $this->term
  ];

  $this->cri1 = ClassResultInfo::factory()
    ->classification($classes1)
    ->create($this->requestData);
  $this->cri2 = ClassResultInfo::factory()
    ->classification($classes2)
    ->create($this->requestData);
  $this->cri3 = ClassResultInfo::factory()
    ->classification($classForGroup2)
    ->create($this->requestData);

  $this->routeName = 'institutions.class-result-info.set-resumption-date';
});

it('sets resumption date for all classes in a class group', function () {
  $resumptionDate = now()
    ->addMonth()
    ->toDateString();

  // If classgroup is not supplied, for_all_classes flag should be there
  actingAs($this->instAdmin)
    ->postJson(route($this->routeName, [$this->institution->uuid]), [
      ...$this->requestData,
      'next_term_resumption_date' => $resumptionDate
    ])
    ->assertJsonValidationErrorFor('for_all_classes');

  actingAs($this->instAdmin)
    ->postJson(
      route($this->routeName, [$this->institution->uuid, $this->classGroup1]),
      [...$this->requestData, 'next_term_resumption_date' => $resumptionDate]
    )
    // ->dump()
    ->assertOk();

  expect($this->cri1->fresh()->next_term_resumption_date->toDateString())->toBe(
    $resumptionDate
  );
  expect($this->cri2->fresh()->next_term_resumption_date->toDateString())->toBe(
    $resumptionDate
  );
  expect($this->cri3->fresh()->next_term_resumption_date?->toDateString())
    ->not()
    ->toBe($resumptionDate);
});

it('sets resumption date for all classes', function () {
  $resumptionDate = now()
    ->addMonth()
    ->toDateString();

  actingAs($this->instAdmin)
    ->postJson(route($this->routeName, [$this->institution->uuid]), [
      ...$this->requestData,
      'for_all_classes' => true,
      'next_term_resumption_date' => $resumptionDate
    ])
    ->assertOk();

  expect($this->cri1->fresh()->next_term_resumption_date->toDateString())->toBe(
    $resumptionDate
  );
  expect($this->cri2->fresh()->next_term_resumption_date->toDateString())->toBe(
    $resumptionDate
  );
  expect($this->cri3->fresh()->next_term_resumption_date->toDateString())->toBe(
    $resumptionDate
  );
});
