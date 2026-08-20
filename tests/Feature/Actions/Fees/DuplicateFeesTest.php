<?php

use App\Actions\Fees\DuplicateFees;
use App\Enums\PaymentInterval;
use App\Enums\TermType;
use App\Models\AcademicSession;
use App\Models\Classification;
use App\Models\Fee;
use App\Models\FeeCategory;
use App\Models\Institution;
use App\Models\InstitutionSetting;

beforeEach(function () {
  $this->institution = Institution::factory()->create();
  $this->session = AcademicSession::factory()->create(['order_index' => 100]);
  $this->classification = Classification::factory()
    ->withInstitution($this->institution)
    ->create();

  InstitutionSetting::factory()
    ->term($this->institution, TermType::Second->value)
    ->create();
  InstitutionSetting::factory()
    ->academicSession($this->institution, $this->session)
    ->create();
});

test(
  'duplicates a previous-term fee into the current term with items and categories',
  function () {
    $sourceFee = Fee::factory()
      ->institution($this->institution)
      ->create([
        'title' => 'Tuition Fee',
        'amount' => 5000,
        'payment_interval' => PaymentInterval::Termly->value,
        'term' => TermType::First->value,
        'academic_session_id' => $this->session->id,
        'fee_items' => [['title' => 'Tuition', 'amount' => 5000]]
      ]);
    FeeCategory::factory()
      ->fee($sourceFee)
      ->feeable($this->classification)
      ->create();

    $result = DuplicateFees::run($this->institution, [$sourceFee->id]);

    expect($result['created'])->toHaveCount(1);
    expect($result['skipped'])->toHaveCount(0);

    $newFee = $result['created']->first();
    expect($newFee->title)->toBe('Tuition Fee');
    expect($newFee->amount)->toBe(5000.0);
    expect($newFee->term)->toBe(TermType::Second);
    expect($newFee->academic_session_id)->toBe($this->session->id);
    expect($newFee->fee_items->getArrayCopy())->toBe([
      ['title' => 'Tuition', 'amount' => 5000]
    ]);

    $newFee->load('feeCategories');
    expect($newFee->feeCategories)->toHaveCount(1);
    expect($newFee->feeCategories->first()->feeable_id)->toBe(
      $this->classification->id
    );
  }
);

test(
  'skips a fee that has already been duplicated into the current term',
  function () {
    $sourceFee = Fee::factory()
      ->institution($this->institution)
      ->create([
        'title' => 'Tuition Fee',
        'payment_interval' => PaymentInterval::Termly->value,
        'term' => TermType::First->value,
        'academic_session_id' => $this->session->id
      ]);
    FeeCategory::factory()
      ->fee($sourceFee)
      ->feeable($this->classification)
      ->create();

    Fee::factory()
      ->institution($this->institution)
      ->create([
        'title' => 'Tuition Fee',
        'payment_interval' => PaymentInterval::Termly->value,
        'term' => TermType::Second->value,
        'academic_session_id' => $this->session->id
      ]);

    $result = DuplicateFees::run($this->institution, [$sourceFee->id]);

    expect($result['created'])->toHaveCount(0);
    expect($result['skipped'])->toHaveCount(1);
    expect(
      Fee::query()
        ->where('title', 'Tuition Fee')
        ->where('term', TermType::Second->value)
        ->count()
    )->toBe(1);
  }
);

test(
  'ignores fee ids that are not part of the resolved previous term',
  function () {
    $unrelatedFee = Fee::factory()
      ->institution($this->institution)
      ->create([
        'title' => 'Unrelated Fee',
        'payment_interval' => PaymentInterval::Termly->value,
        'term' => TermType::Third->value,
        'academic_session_id' => $this->session->id
      ]);

    $result = DuplicateFees::run($this->institution, [$unrelatedFee->id]);

    expect($result['created'])->toHaveCount(0);
    expect($result['skipped'])->toHaveCount(0);
    expect(
      Fee::query()
        ->where('title', 'Unrelated Fee')
        ->where('term', TermType::Second->value)
        ->exists()
    )->toBeFalse();
  }
);
