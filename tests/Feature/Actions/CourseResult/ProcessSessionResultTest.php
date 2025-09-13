<?php

use App\Actions\CourseResult\ProcessSessionResult;
use App\Enums\TermType;
use App\Models\AcademicSession;
use App\Models\Classification;
use App\Models\Institution;
use App\Models\SessionResult;
use App\Models\Student;
use App\Models\TermResult;

beforeEach(function () {
  // Mock current institution helper
  $this->institution = Institution::factory()->create();

  $this->session = AcademicSession::factory()->create();
  $this->classification = Classification::factory()
    ->withInstitution($this->institution)
    ->create();
  $this->student = Student::factory()
    ->withInstitution($this->institution, $this->classification)
    ->create();
});

it('processes term results and persists session results', function () {
  /** @var TermResult $tr1 */
  $tr1 = TermResult::factory()
    ->forStudent($this->student)
    ->create([
      'academic_session_id' => $this->session->id,
      'term' => TermType::First,
      'total_score' => 60,
      'for_mid_term' => false
    ]);

  /** @var TermResult $tr2 */
  $tr2 = TermResult::factory()
    ->forStudent($this->student)
    ->create([
      'academic_session_id' => $this->session->id,
      'term' => TermType::Second,
      'total_score' => 70,
      'for_mid_term' => false
    ]);

  /** @var TermResult $tr3 */
  $tr3 = TermResult::factory()
    ->forStudent($this->student)
    ->create([
      'academic_session_id' => $this->session->id,
      'term' => TermType::Third,
      'total_score' => 80,
      'for_mid_term' => false
    ]);

  $student2 = Student::factory()
    ->withInstitution($this->institution, $this->classification)
    ->create();
  /** @var TermResult $tr21 */
  $tr21 = TermResult::factory()
    ->forStudent($student2)
    ->create([
      'academic_session_id' => $this->session->id,
      'term' => TermType::First,
      'total_score' => 60,
      'for_mid_term' => false
    ]);

  /** @var TermResult $tr22 */
  $tr22 = TermResult::factory()
    ->forStudent($student2)
    ->create([
      'academic_session_id' => $this->session->id,
      'term' => TermType::Second,
      'total_score' => 70,
      'for_mid_term' => false
    ]);

  /** @var TermResult $tr23 */
  $tr23 = TermResult::factory()
    ->forStudent($student2)
    ->create([
      'academic_session_id' => $this->session->id,
      'term' => TermType::Third,
      'total_score' => 80,
      'for_mid_term' => false
    ]);

  // Run the action
  ProcessSessionResult::run($this->session, $this->classification);

  // Assert session result was created
  $sessionResult = $this->student->sessionResults()->first();
  $sessionResult2 = $student2->sessionResults()->first();

  $average1 = round(($tr1->average + $tr2->average + $tr3->average) / 3, 2);
  $average2 = round(($tr21->average + $tr22->average + $tr23->average) / 3, 2);
  expect($sessionResult)->not->toBeNull();
  expect($sessionResult->result)->toBe(210.0); // 60 + 70 + 80
  expect($sessionResult->average)->toBe($average1);
  expect($sessionResult2->average)->toBe($average2);
  expect($sessionResult->grade)->toBeString();
  expect($sessionResult->position)->toBe($average1 >= $average2 ? 1 : 2);
});

it('handles students with missing term results gracefully', function () {
  /** @var TermResult $tr1 */
  $tr1 = TermResult::factory()
    ->forStudent($this->student)
    ->create([
      'academic_session_id' => $this->session->id,
      'term' => TermType::First,
      'total_score' => 50,
      'for_mid_term' => false
    ]);

  /** @var TermResult $tr2 */
  $tr2 = TermResult::factory()
    ->forStudent($this->student)
    ->create([
      'academic_session_id' => $this->session->id,
      'term' => TermType::Third,
      'total_score' => 90,
      'for_mid_term' => false
    ]);

  // Run the action
  ProcessSessionResult::run($this->session, $this->classification);

  // Assert session result was created
  $sessionResult = SessionResult::where(
    'student_id',
    $this->student->id
  )->first();

  expect($sessionResult)->not->toBeNull();
  expect($sessionResult->result)->toBe(140.0); // 50 + 0 + 90
  expect($sessionResult->average)->toBe(
    round(($tr1->average + $tr2->average) / 2, 2)
  );
  expect($sessionResult->position)->toBe(1);
  expect($sessionResult->grade)->toBeString(); // depends on GetGrade::run()
});

it(
  'does not create session result if third term results are missing',
  function () {
    /** @var TermResult $tr1 */
    TermResult::factory()
      ->forStudent($this->student)
      ->create([
        'academic_session_id' => $this->session->id,
        'term' => TermType::First,
        'total_score' => 55,
        'for_mid_term' => false
      ]);

    /** @var TermResult $tr2 */
    TermResult::factory()
      ->forStudent($this->student)
      ->create([
        'academic_session_id' => $this->session->id,
        'term' => TermType::Second,
        'total_score' => 65,
        'for_mid_term' => false
      ]);

    // Run the action
    ProcessSessionResult::run($this->session, $this->classification);

    // Assert session result was NOT created
    $sessionResult = SessionResult::where(
      'student_id',
      $this->student->id
    )->first();

    expect($sessionResult)->toBeNull();
  }
);
