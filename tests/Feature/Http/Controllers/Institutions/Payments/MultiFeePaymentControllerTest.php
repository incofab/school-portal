<?php

use App\Enums\InstitutionUserType;
use App\Enums\TermType;
use App\Models\AcademicSession;
use App\Models\Fee;
use App\Models\Institution;
use App\Models\InstitutionUser;
use App\Models\ReceiptType;
use App\Models\Student;
use Illuminate\Support\Str;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;

beforeEach(function () {
  // Create a user and assign the admin role
  $this->admin = InstitutionUser::factory()->create([
    'role' => InstitutionUserType::Admin
  ]);
  $this->institution = Institution::factory()->create();
  $this->admin = $this->institution->createdBy;
  $this->receiptType = ReceiptType::factory()
    ->institution($this->institution)
    ->create();
  $this->fees = Fee::factory(3)
    ->institution($this->institution)
    ->receiptType($this->receiptType)
    ->create();
  $this->student = Student::factory()
    ->withInstitution($this->institution)
    ->create();
  $this->academicSession = AcademicSession::factory()->create();
});

it('stores a fee payment successfully', function () {
  $reference = Str::uuid();
  $data = [
    'reference' => $reference,
    'user_id' => $this->student->user_id,
    'academic_session_id' => $this->academicSession->id,
    'term' => TermType::First->value,
    'method' => 'credit_card',
    'transaction_reference' => randomDigits(10),
    'fee_ids' => $this->fees->map->id->toArray()
  ];

  actingAs($this->admin)
    ->postJson(
      route(
        'institutions.fee-payments.multi-fee-payment.store',
        $this->institution
      ),
      $data
    )
    ->assertOk();

  // Handles duplicate
  actingAs($this->admin)
    ->postJson(
      route(
        'institutions.fee-payments.multi-fee-payment.store',
        $this->institution
      ),
      $data
    )
    ->assertOk();

  assertDatabaseCount('fee_payments', $this->fees->count());
  assertDatabaseHas('fee_payments', [
    'fee_id' => $this->fees->first()->id,
    'user_id' => $this->student->user_id
  ]);
});
