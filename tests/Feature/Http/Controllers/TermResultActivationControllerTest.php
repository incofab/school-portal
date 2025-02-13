<?php

use App\Enums\TermType;
use App\Models\AcademicSession;
use App\Models\Pin;
use App\Models\Student;
use App\Models\TermResult;
use App\Models\Institution;

use function Pest\Laravel\postJson;

beforeEach(function () {
  $this->institution = Institution::factory()->create();
  $this->instAdmin = $this->institution->createdBy;
  $this->academicSession = AcademicSession::factory()->create();
  $this->termResult = TermResult::factory()
    ->withInstitution($this->institution)
    ->for($this->academicSession)
    ->create();
  $this->student = $this->termResult->student;

  // $this->successRoute = route('institutions.students.result-sheet', [
  //   $this->institution->uuid,
  //   $this->termResult->student_id,
  //   $this->termResult->classification_id,
  //   $this->termResult->academic_session_id,
  //   $this->termResult->term,
  //   $this->termResult->for_mid_term ? 1 : 0
  // ]);
});

// it('handles used pin', function () {
//   $pin = Pin::factory()
//     ->withInstitution($this->institution)
//     ->forStudent($this->student)
//     ->for($this->academicSession)
//     ->used()
//     ->create();

//   postJson(route('activate-term-result.store'), [
//     'student_code' => $this->student->code,
//     'pin' => $pin->pin
//   ])->assertJsonValidationErrorFor('pin');
//   $pin->fill(['term_result_id' => $this->termResult->id])->save();
//   postJson(route('activate-term-result.store'), [
//     'student_code' => $this->student->code,
//     'pin' => $pin->pin
//   ])->assertOk();
// });

it('should handle invalid pin', function () {
  postJson(route('activate-term-result.store'), [
    'student_code' => $this->student->code,
    'pin' => 'invalid_pin'
  ])->assertJsonValidationErrorFor('pin');
});

it('should handle invalid student code', function () {
  $pin = Pin::factory()
    ->withInstitution($this->institution)
    ->forStudent($this->student)
    ->for($this->academicSession)
    ->create();
  $student2 = Student::factory()
    ->withInstitution(Institution::factory()->create())
    ->create();

  postJson(route('activate-term-result.store'), [
    'student_code' => 'invalid_student_code',
    'pin' => $pin->pin
  ])->assertJsonValidationErrorFor('student_code');

  postJson(route('activate-term-result.store'), [
    'student_code' => $student2->code,
    'pin' => $pin->pin
  ])->assertNotFound();
  // ])->assertJsonValidationErrorFor('student_code');
});

it('should handle pin not for student', function () {
  $student2 = Student::factory()
    ->withInstitution($this->institution)
    ->create();

  $pin = Pin::factory()
    ->withInstitution($this->institution)
    ->forStudent($student2)
    ->for($this->academicSession)
    ->create();

  postJson(route('activate-term-result.store'), [
    'student_code' => $this->student->code,
    'pin' => $pin->pin
  ])->assertJsonValidationErrorFor('pin');
});

it('ensures that nothing happens if student has no result', function () {
  $pin = Pin::factory()
    ->withInstitution($this->institution)
    ->create();
  $student2 = Student::factory()
    ->withInstitution($this->institution)
    ->create();

  postJson(route('activate-term-result.store'), [
    'student_code' => $student2->code,
    'pin' => $pin->pin
  ])
    ->assertOk()
    ->assertJsonPath('redirect_url', route('student-login'));
  expect($pin->fresh())->used_at->toBeNull();
});

it('returns multiple term results if they are more than one', function () {
  $pin = Pin::factory()
    ->withInstitution($this->institution)
    ->create();
  TermResult::factory()
    ->withInstitution($this->institution)
    ->for(AcademicSession::factory()->create())
    ->forStudent($this->student)
    ->create();

  postJson(route('activate-term-result.store'), [
    'student_code' => $this->student->code,
    'pin' => $pin->pin
  ])
    ->assertOk()
    ->assertJsonFragment(['has_multiple_results' => true])
    ->assertJsonCount(2, 'term_results');
  expect($pin->fresh())->used_at->toBeNull();
});

it('should activate term result with student pin', function () {
  $pin = Pin::factory()
    ->withInstitution($this->institution)
    ->forStudent($this->student)
    ->for($this->academicSession)
    ->create();

  postJson(route('activate-term-result.store'), [
    'student_code' => $this->student->code,
    'pin' => $pin->pin
  ])
    ->assertOk()
    ->assertJsonPath('activated', true);
  expect($this->termResult->fresh())->is_activated->toBe(true);
  expect($pin->fresh())
    ->term_result_id->toBe($this->termResult->id)
    ->used_at->not()
    ->toBeNull();
});

it('should activate term result with normal pin', function () {
  $pin = Pin::factory()
    ->withInstitution($this->institution)
    ->create();

  postJson(route('activate-term-result.store'), [
    'student_code' => $this->student->code,
    'pin' => $pin->pin
  ])
    ->assertOk()
    ->assertJsonPath('activated', true);
  expect($this->termResult->fresh())->is_activated->toBe(true);
  expect($pin->fresh())
    ->term_result_id->toBe($this->termResult->id)
    ->used_at->not()
    ->toBeNull();
});

it('activates a particular term result with normal pin', function () {
  $pin = Pin::factory()
    ->withInstitution($this->institution)
    ->create();

  postJson(route('activate-term-result.store'), [
    'student_code' => $this->student->code,
    'pin' => $pin->pin,
    'term_result_id' => $this->termResult->id
  ])
    ->assertOk()
    ->assertJsonPath('activated', true);
  expect($this->termResult->fresh())->is_activated->toBe(true);
  expect($pin->fresh())
    ->term_result_id->toBe($this->termResult->id)
    ->used_at->not()
    ->toBeNull();
});

it('uses same pin to activate other results in the same session', function () {
  $pin = Pin::factory()
    ->withInstitution($this->institution)
    ->create();

  postJson(route('activate-term-result.store'), [
    'student_code' => $this->student->code,
    'pin' => $pin->pin
  ])->assertJsonPath('activated', true);
  expect($this->termResult->fresh())->is_activated->toBe(true);

  $termResult2 = TermResult::factory()
    ->withInstitution($this->institution)
    ->for($this->academicSession)
    ->forStudent($this->student)
    ->create(['term' => TermType::Second->value]);

  postJson(route('activate-term-result.store'), [
    'student_code' => $this->student->code,
    'pin' => $pin->pin
  ])->assertOk();
  expect($termResult2->fresh())->is_activated->toBe(true);

  $academicSession2 = AcademicSession::factory()->create();
  $academicSession2TermResult = TermResult::factory()
    ->withInstitution($this->institution)
    ->for($academicSession2)
    ->forStudent($this->student)
    ->create();

  postJson(route('activate-term-result.store'), [
    'student_code' => $this->student->code,
    'pin' => $pin->pin
  ])->assertOk();
  expect($academicSession2TermResult->fresh())->is_activated->toBe(false);
});
