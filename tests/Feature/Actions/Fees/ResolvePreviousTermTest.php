<?php

use App\Actions\Fees\ResolvePreviousTerm;
use App\Enums\TermType;
use App\Models\AcademicSession;
use App\Models\Institution;
use App\Models\InstitutionSetting;

beforeEach(function () {
  $this->institution = Institution::factory()->create();
});

test('previous term within the same session is the term before it', function () {
  $session = AcademicSession::factory()->create(['order_index' => 100]);
  InstitutionSetting::factory()
    ->term($this->institution, TermType::Second->value)
    ->create();
  InstitutionSetting::factory()
    ->academicSession($this->institution, $session)
    ->create();

  $result = ResolvePreviousTerm::run($this->institution);

  expect($result['term'])->toBe(TermType::First);
  expect($result['academic_session_id'])->toBe($session->id);
});

test(
  'previous term for the third term is the second term in the same session',
  function () {
    $session = AcademicSession::factory()->create(['order_index' => 100]);
    InstitutionSetting::factory()
      ->term($this->institution, TermType::Third->value)
      ->create();
    InstitutionSetting::factory()
      ->academicSession($this->institution, $session)
      ->create();

    $result = ResolvePreviousTerm::run($this->institution);

    expect($result['term'])->toBe(TermType::Second);
    expect($result['academic_session_id'])->toBe($session->id);
  }
);

test(
  'previous term for the first term wraps to the third term of the previous session',
  function () {
    $previousSession = AcademicSession::factory()->create([
      'order_index' => 100
    ]);
    $currentSession = AcademicSession::factory()->create([
      'order_index' => 101
    ]);
    InstitutionSetting::factory()
      ->term($this->institution, TermType::First->value)
      ->create();
    InstitutionSetting::factory()
      ->academicSession($this->institution, $currentSession)
      ->create();

    $result = ResolvePreviousTerm::run($this->institution);

    expect($result['term'])->toBe(TermType::Third);
    expect($result['academic_session_id'])->toBe($previousSession->id);
  }
);

test(
  'resolves no previous session when there is none before the current one',
  function () {
    $session = AcademicSession::factory()->create(['order_index' => 100]);
    InstitutionSetting::factory()
      ->term($this->institution, TermType::First->value)
      ->create();
    InstitutionSetting::factory()
      ->academicSession($this->institution, $session)
      ->create();

    $result = ResolvePreviousTerm::run($this->institution);

    expect($result['term'])->toBe(TermType::Third);
    expect($result['academic_session_id'])->toBeNull();
  }
);
