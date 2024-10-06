<?php

use App\Enums\InstitutionUserType;
use App\Models\Classification;
use App\Models\Institution;
use App\Models\Student;
use App\Models\StudentClassMovement;

use function Pest\Laravel\actingAs;

beforeEach(function () {
  $this->institution = Institution::factory()->create();
  $this->instAdmin = $this->institution->createdBy;
});

it('reverts a single student class movement', function () {
  $arr = [
    ($studentClassMovement = StudentClassMovement::factory()
      ->withInstitution($this->institution)
      ->create()),
    ($studentClassMovement = StudentClassMovement::factory()
      ->withInstitution($this->institution)
      ->fromAlumni()
      ->create()),
    ($studentClassMovement = StudentClassMovement::factory()
      ->withInstitution($this->institution)
      ->toAlumni()
      ->create())
  ];
  /** @var StudentClassMovement $studentClassMovement */
  foreach ($arr as $key => $studentClassMovement) {
    $route = route('institutions.student-class-movements.revert', [
      $this->institution->uuid,
      $studentClassMovement
    ]);

    actingAs($this->instAdmin)
      ->postJson($route)
      ->assertOk();

    $newMovement = StudentClassMovement::query()
      ->latest('id')
      ->first();

    expect($newMovement)
      ->destination_classification_id->toBe(
        $studentClassMovement->source_classification_id
      )
      ->source_classification_id->toBe(
        $studentClassMovement->destination_classification_id
      )
      ->student_id->toBe($studentClassMovement->student_id)
      ->revert_reference_id->toBe($studentClassMovement->id);

    if ($newMovement->moveFromAlumni()) {
      expect($newMovement->student->institutionUser->role)->toBe(
        InstitutionUserType::Student
      );
    }
    if ($newMovement->moveToAlumni()) {
      expect($newMovement->student->institutionUser->role)->toBe(
        InstitutionUserType::Alumni
      );
    }
  }
});

it('reverts a batch of student class movements', function () {
  $batchNo = uniqid();
  $count = 5;
  StudentClassMovement::factory()
    ->withInstitution($this->institution)
    ->count($count)
    ->create(['batch_no' => $batchNo]);
  $route = route('institutions.student-class-movements.batch-revert', [
    $this->institution->uuid
  ]);

  actingAs($this->instAdmin)
    ->postJson($route, ['batch_no' => $batchNo])
    ->assertOk();

  expect(StudentClassMovement::query()->count())->toBe($count * 2);
});

it('moves a batch of student class movements to another class', function () {
  $batchNo = uniqid();
  $count = 5;
  $destinationClass = Classification::factory()
    ->withInstitution($this->institution)
    ->create();
  StudentClassMovement::factory()
    ->withInstitution($this->institution)
    ->count($count)
    ->create(['batch_no' => $batchNo]);
  $route = route('institutions.student-class-movements.batch-revert', [
    $this->institution->uuid
  ]);

  actingAs($this->instAdmin)
    ->postJson($route, [
      'batch_no' => $batchNo,
      'change_class' => true,
      'destination_classification_id' => $destinationClass->id
    ])
    ->assertOk();

  expect($destinationClass->students()->count())->toBe($count);
});

it('moves a batch of student class movements to alumni', function () {
  $batchNo = uniqid();
  $count = 5;

  StudentClassMovement::factory()
    ->withInstitution($this->institution)
    ->count($count)
    ->create(['batch_no' => $batchNo]);
  $route = route('institutions.student-class-movements.batch-revert', [
    $this->institution->uuid
  ]);

  actingAs($this->instAdmin)
    ->postJson($route, [
      'batch_no' => $batchNo,
      'change_class' => true,
      'destination_classification_id' => null
    ])
    ->assertJsonValidationErrorFor('destination_classification_id');
  actingAs($this->instAdmin)
    ->postJson($route, [
      'batch_no' => $batchNo,
      'change_class' => true,
      'destination_classification_id' => null,
      'move_to_alumni' => true
    ])
    ->assertOk();

  expect(Student::whereNull('classification_id')->count())->toBe($count);
});
