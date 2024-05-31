<?php

use App\Models\FeePayment;
use App\Models\Receipt;
use App\Models\Student;
use App\Models\Institution;

use function Pest\Laravel\actingAs;

beforeEach(function () {
  $this->institution = Institution::factory()->create();
  $this->admin = $this->institution->createdBy;
  $this->student = Student::factory()
    ->withInstitution($this->institution)
    ->create();
  $this->receipt = Receipt::factory()
    ->institution($this->institution)
    ->student($this->student)
    ->create();
});

it('can load the index page with fee payments and receipt', function () {
  FeePayment::factory(3)->receipt($this->receipt);
  actingAs($this->admin)
    ->get(
      route('institutions.users.fee-payments.index', [
        'institution' => $this->institution->uuid,
        'user' => $this->student->user_id,
        'receipt' => $this->receipt
      ])
    )
    ->assertInertia(
      fn($page) => $page
        ->component('institutions/students/payments/list-student-fee-payments')
        ->has('receipt')
        ->has('feePayments.data')
    );
});

it('can load the index page for receipts', function () {
  actingAs($this->admin)
    ->get(
      route('institutions.users.receipts.index', [
        'institution' => $this->institution->uuid,
        'user' => $this->student->user_id
      ])
    )
    ->assertInertia(
      fn($page) => $page
        ->component('institutions/students/payments/list-student-receipts')
        ->has('receipt')
        ->has('receipts.data')
    );
});
