<?php

use App\Models\AcademicSession;
use App\Models\Classification;
use App\Models\ClassificationGroup;
use App\Models\Institution;
use App\Models\SessionResult;
use App\Support\SettingsHandler;

use function Pest\Laravel\actingAs;

beforeEach(function () {
  SettingsHandler::clear();
  $this->institution = Institution::factory()->create();
  $this->academicSession = AcademicSession::factory()->create();
  $this->instAdmin = $this->institution->createdBy;

  $this->classificationGroup = ClassificationGroup::factory()
    ->withInstitution($this->institution)
    ->create();

  $this->classification = Classification::factory()
    ->classificationGroup($this->classificationGroup)
    ->create();

  $this->route = route(
    'institutions.classification-groups.promote-students.store',
    [$this->institution->uuid, $this->classificationGroup]
  );
});

it('moves students to another class based on session result', function () {
  $sessionResults = SessionResult::factory(5)
    ->academicSession($this->academicSession)
    ->classification($this->classification)
    ->create();

  [$class1, $class2] = Classification::factory(2)
    ->withInstitution($this->institution)
    ->create();
  $class1Range = [0, 50];
  $class2Range = [51, 100];

  $requestData = [
    'academic_session_id' => $this->academicSession->id,
    'promotions' => [
      [
        'destination_classification_id' => $class1->id,
        'from' => $class1Range[0],
        'to' => $class1Range[1]
      ],
      [
        'destination_classification_id' => $class2->id,
        'from' => $class2Range[0],
        'to' => $class2Range[1]
      ]
    ]
  ];

  actingAs($this->instAdmin)
    ->postJson($this->route, $requestData)
    ->assertOk();

  foreach ($sessionResults as $key => $sessionResult) {
    expect($sessionResult->fresh()->student->classification_id)->toBe(
      $sessionResult->result < 51 ? $class1->id : $class2->id
    );
  }
});
