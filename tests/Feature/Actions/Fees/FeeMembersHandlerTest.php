<?php

use App\Actions\Fees\FeeMembersHandler;
use App\Models\Classification;
use App\Models\Fee;
use App\Models\FeeCategory;
use App\Models\Institution;
use App\Models\Student;

beforeEach(function () {
  $this->institution = Institution::factory()->create();
  $this->classification = Classification::factory()
    ->withInstitution($this->institution)
    ->create();
});

it(
  'returns only the individually targeted student when that is the only category',
  function () {
    $targetedStudent = Student::factory()
      ->withInstitution($this->institution, $this->classification)
      ->create();
    $otherStudent = Student::factory()
      ->withInstitution($this->institution, $this->classification)
      ->create();

    $fee = Fee::factory()
      ->institution($this->institution)
      ->create();
    FeeCategory::factory()
      ->fee($fee)
      ->feeable($targetedStudent)
      ->create();

    $members = (new FeeMembersHandler($this->institution))->getFeeMembers($fee);

    expect($members->pluck('id')->toArray())->toBe([$targetedStudent->user_id]);
    expect($members->pluck('id'))->not->toContain($otherStudent->user_id);
  }
);

it('combines class-targeted and individually targeted students', function () {
  $classStudent = Student::factory()
    ->withInstitution($this->institution, $this->classification)
    ->create();

  $otherClassification = Classification::factory()
    ->withInstitution($this->institution)
    ->create();
  $individualStudent = Student::factory()
    ->withInstitution($this->institution, $otherClassification)
    ->create();

  $unrelatedStudent = Student::factory()
    ->withInstitution($this->institution, $otherClassification)
    ->create();

  $fee = Fee::factory()
    ->institution($this->institution)
    ->create();
  FeeCategory::factory()
    ->fee($fee)
    ->feeable($this->classification)
    ->create();
  FeeCategory::factory()
    ->fee($fee)
    ->feeable($individualStudent)
    ->create();

  $members = (new FeeMembersHandler($this->institution))->getFeeMembers($fee);
  $memberUserIds = $members->pluck('id')->toArray();

  expect($memberUserIds)->toContain($classStudent->user_id);
  expect($memberUserIds)->toContain($individualStudent->user_id);
  expect($memberUserIds)->not->toContain($unrelatedStudent->user_id);
});
