<?php

use App\Actions\Fees\GetFeeCollectionOverview;
use App\Enums\InstitutionUserType;
use App\Enums\ReceiptStatus;
use App\Models\Classification;
use App\Models\Fee;
use App\Models\FeeCategory;
use App\Models\FeePayment;
use App\Models\Institution;
use App\Models\Receipt;
use App\Models\Student;
use Illuminate\Pagination\LengthAwarePaginator;

beforeEach(function () {
  $this->institution = Institution::factory()->create();
  $this->classification = Classification::factory()
    ->withInstitution($this->institution)
    ->create(['title' => 'JSS 1']);
  $this->fee = Fee::factory()
    ->institution($this->institution)
    ->create(['amount' => 10000]);
  FeeCategory::factory()
    ->fee($this->fee)
    ->feeable($this->institution)
    ->create();
});

function overviewStudent(Institution $institution, Classification $classification)
{
  return Student::factory()
    ->withInstitution($institution, $classification)
    ->create();
}

function overviewReceipt(
  Fee $fee,
  Student $student,
  float $amountPaid,
  float $amountRemaining,
  string $status
) {
  $receipt = Receipt::factory()
    ->fee($fee)
    ->student($student)
    ->create([
      'amount' => $fee->amount,
      'amount_paid' => $amountPaid,
      'amount_remaining' => $amountRemaining,
      'status' => $status
    ]);

  FeePayment::factory()
    ->fee($fee)
    ->receipt($receipt)
    ->create(['amount' => $amountPaid]);

  return $receipt;
}

it('summarizes eligible students by payment status and balance', function () {
  $paid = overviewStudent($this->institution, $this->classification);
  $partial = overviewStudent($this->institution, $this->classification);
  $unpaid = overviewStudent($this->institution, $this->classification);

  overviewReceipt(
    $this->fee,
    $paid,
    10000,
    0,
    ReceiptStatus::Paid->value
  );
  overviewReceipt(
    $this->fee,
    $partial,
    4000,
    6000,
    ReceiptStatus::Partial->value
  );

  $overview = (new GetFeeCollectionOverview(
    $this->institution,
    $this->fee
  ))->run();

  expect($overview['summary'])->toMatchArray([
    'total_students' => 3,
    'paid' => 1,
    'partial' => 1,
    'unpaid' => 1,
    'amount_expected' => 30000,
    'amount_paid' => 14000,
    'amount_remaining' => 16000
  ]);
  expect($overview['students'])->toBeInstanceOf(LengthAwarePaginator::class);
  expect($overview['students']->total())->toBe(3);
});

it('limits the overview to the selected class and status', function () {
  $otherClass = Classification::factory()
    ->withInstitution($this->institution)
    ->create(['title' => 'JSS 2']);
  $classStudent = overviewStudent($this->institution, $this->classification);
  $otherClassStudent = overviewStudent($this->institution, $otherClass);

  overviewReceipt(
    $this->fee,
    $classStudent,
    10000,
    0,
    ReceiptStatus::Paid->value
  );

  $overview = (new GetFeeCollectionOverview(
    $this->institution,
    $this->fee
  ))->run($this->classification->id, 'paid');

  expect($overview['summary']['total_students'])->toBe(1)
    ->and($overview['summary']['paid'])->toBe(1)
    ->and($overview['students']->total())->toBe(1)
    ->and($overview['students']->getCollection()->first()['student']->id)
    ->toBe($classStudent->id)
    ->and($overview['students']->getCollection()->pluck('student.id'))
    ->not
    ->toContain($otherClassStudent->id);
});

it('uses the fee class and group assignments to determine recipients', function () {
  $classFee = Fee::factory()
    ->institution($this->institution)
    ->create(['title' => 'Class fee']);
  FeeCategory::factory()
    ->fee($classFee)
    ->feeable($this->classification)
    ->create();

  $classStudent = overviewStudent($this->institution, $this->classification);
  $otherClass = Classification::factory()
    ->withInstitution($this->institution)
    ->create();
  $otherClassStudent = overviewStudent($this->institution, $otherClass);

  $classOverview = (new GetFeeCollectionOverview(
    $this->institution,
    $classFee
  ))->run();

  expect($classOverview['students']->getCollection()->pluck('student.id'))
    ->toContain($classStudent->id)
    ->not
    ->toContain($otherClassStudent->id);

  $group = $this->classification->classificationGroup;
  $groupFee = Fee::factory()
    ->institution($this->institution)
    ->create(['title' => 'Group fee']);
  FeeCategory::factory()
    ->fee($groupFee)
    ->feeable($group)
    ->create();

  $groupClass = Classification::factory()
    ->classificationGroup($group)
    ->create();
  $groupStudent = overviewStudent($this->institution, $groupClass);

  $groupOverview = (new GetFeeCollectionOverview(
    $this->institution,
    $groupFee
  ))->run();

  expect($groupOverview['students']->getCollection()->pluck('student.id'))
    ->toContain($groupStudent->id)
    ->not
    ->toContain($otherClassStudent->id);
});

it('does not count institution staff as fee recipients', function () {
  $student = overviewStudent($this->institution, $this->classification);
  $staff = $this->institution->institutionUsers()->create([
    'user_id' => \App\Models\User::factory()->create()->id,
    'type' => InstitutionUserType::Accountant->value,
    'status' => 'active'
  ]);

  $overview = (new GetFeeCollectionOverview(
    $this->institution,
    $this->fee
  ))->run();

  expect($overview['summary']['total_students'])->toBe(1)
    ->and($overview['students']->getCollection()->first()['student']->id)
    ->toBe($student->id)
    ->and($staff->id)->not->toBe($student->institution_user_id);
});
