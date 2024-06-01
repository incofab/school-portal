<?php

use App\Enums\InstitutionUserType;
use App\Enums\TermType;
use App\Models\AcademicSession;
use App\Models\Fee;
use App\Models\Institution;
use App\Models\InstitutionUser;
use App\Models\Student;
use Illuminate\Support\Str;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;

beforeEach(function () {
  // Create a user and assign the admin role
  $this->admin = InstitutionUser::factory()->create([
    'role' => InstitutionUserType::Admin
  ]);
  $this->institution = Institution::factory()->create();
  $this->admin = $this->institution->createdBy;
  $this->fee = Fee::factory()
    ->institution($this->institution)
    ->create();
  $this->student = Student::factory()
    ->withInstitution($this->institution)
    ->create();
  $this->academicSession = AcademicSession::factory()->create();
});

it('stores a fee payment successfully', function () {
  $reference = Str::uuid();
  $amount = 1000;
  $data = [
    'reference' => $reference,
    'fee_id' => $this->fee->id,
    'user_id' => $this->student->user_id,
    'amount' => $amount,
    'academic_session_id' => $this->academicSession->id,
    'term' => TermType::First->value,
    'method' => 'credit_card'
  ];

  actingAs($this->admin)
    ->postJson(
      route('institutions.fee-payments.store', $this->institution),
      $data
    )
    ->assertOk()
    ->assertJsonStructure(['feePayment']);

  assertDatabaseHas('fee_payments', [
    'fee_id' => $this->fee->id,
    'user_id' => $this->student->user_id,
    'amount_paid' => $amount
  ]);
  assertDatabaseHas('fee_payment_tracks', [
    'reference' => $reference,
    'amount' => $amount
  ]);
  assertDatabaseHas('receipts', [
    'institution_id' => $this->institution->id,
    'user_id' => $this->student->user_id,
    'receipt_type_id' => $this->fee->receipt_type_id
  ]);
});
