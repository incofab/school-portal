<?php

use App\Enums\PaymentInterval;
use App\Enums\TermType;
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
  $this->student = Student::factory()
    ->withInstitution($this->institution, $this->classification)
    ->create();
});

test(
  'a fee targeted directly at a student applies to that student',
  function () {
    $fee = Fee::factory()
      ->institution($this->institution)
      ->create([
        'payment_interval' => PaymentInterval::OneTime->value,
        'term' => null,
        'academic_session_id' => null
      ]);

    FeeCategory::factory()
      ->fee($fee)
      ->feeable($this->student)
      ->create();

    expect($fee->forStudent($this->student, $this->classification))->toBeTrue();
  }
);

test(
  'a fee targeted at another student does not apply to this student',
  function () {
    $otherStudent = Student::factory()
      ->withInstitution($this->institution, $this->classification)
      ->create();

    $fee = Fee::factory()
      ->institution($this->institution)
      ->create([
        'payment_interval' => PaymentInterval::OneTime->value,
        'term' => null,
        'academic_session_id' => null
      ]);

    FeeCategory::factory()
      ->fee($fee)
      ->feeable($otherStudent)
      ->create();

    expect(
      $fee->forStudent($this->student, $this->classification)
    )->toBeFalse();
  }
);

test(
  'individually targeted fees are included among a student\'s fees',
  function () {
    $academicSession = \App\Models\AcademicSession::factory()->create();
    $term = TermType::First;

    $classFee = Fee::factory()
      ->institution($this->institution)
      ->create([
        'payment_interval' => PaymentInterval::Termly->value,
        'term' => $term->value,
        'academic_session_id' => $academicSession->id
      ]);
    FeeCategory::factory()
      ->fee($classFee)
      ->feeable($this->classification)
      ->create();

    $individualFee = Fee::factory()
      ->institution($this->institution)
      ->create([
        'title' => 'Individual Late Registration Fee',
        'payment_interval' => PaymentInterval::Termly->value,
        'term' => $term->value,
        'academic_session_id' => $academicSession->id
      ]);
    FeeCategory::factory()
      ->fee($individualFee)
      ->feeable($this->student)
      ->create();

    $studentFees = $this->student
      ->fresh(['classification'])
      ->studentFees($term->value, $academicSession->id);

    $feeIds = collect($studentFees)->pluck('id');

    expect($feeIds)->toContain($classFee->id);
    expect($feeIds)->toContain($individualFee->id);
  }
);
