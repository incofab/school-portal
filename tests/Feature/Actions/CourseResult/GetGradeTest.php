<?php

use App\Actions\CourseResult\GetGrade;
use App\Models\Classification;
use App\Models\Institution;
use App\Models\ResultCommentTemplate;

beforeEach(function () {
  $this->institution = Institution::factory()->create();
  $this->classification = Classification::factory()
    ->withInstitution($this->institution)
    ->create();
});

it(
  'returns the grade from the matching template for the classification',
  function () {
    $template = ResultCommentTemplate::factory()
      ->withInstitution($this->institution)
      ->classification($this->classification)
      ->create([
        'min' => 80,
        'max' => 100,
        'grade' => 'A*'
      ]);
    $grade = GetGrade::run(85, $this->classification);

    expect($grade)->toBe('A*');
  }
);

it('falls back to default grading when no template matches', function () {
  $anotherClassification = Classification::factory()
    ->withInstitution($this->institution)
    ->create();

  $otherTemplate = ResultCommentTemplate::factory()
    ->withInstitution($this->institution)
    ->create([
      'min' => 60,
      'max' => 70,
      'grade' => 'B+'
    ]);

  $otherTemplate->classifications()->attach($anotherClassification);

  $grade = GetGrade::run(62, $this->classification);

  expect($grade)->toBe('B');
});
