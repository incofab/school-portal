<?php

use App\Actions\Payments\FeePaymentHandler;
use App\Enums\Payments\PaymentPurpose;
use App\Enums\Payments\PaymentStatus;
use App\Models\AdmissionApplication;
use App\Models\AdmissionForm;
use App\Models\AdmissionFormPurchase;
use App\Models\BankAccount;
use App\Models\Fee;
use App\Models\Institution;
use App\Models\ManualPayment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;

beforeEach(function () {
  $this->institution = Institution::factory()->create();
  $this->admin = $this->institution->createdBy;
  $this->institutionGroup = $this->institution->institutionGroup;
  $this->student = User::factory()
    ->student($this->institution)
    ->create();
  $this->accountant = User::factory()
    ->accountant($this->institution)
    ->create();
  $this->teacher = User::factory()
    ->teacher($this->institution)
    ->create();
  $this->fee = Fee::factory()
    ->institution($this->institution)
    ->create();
  $this->bankAccount = BankAccount::factory()
    ->accountable($this->institutionGroup)
    ->create();
});

function makeManualPaymentForTest($test, array $attributes = []): ManualPayment
{
  return ManualPayment::factory()
    ->institution($test->institution)
    ->payable($test->student)
    ->paymentable($test->fee)
    ->create([
      'user_id' => $test->student->id,
      'bank_account_id' => $test->bankAccount->id,
      'amount' => $test->fee->amount,
      'purpose' => PaymentPurpose::Fee->value,
      ...$attributes
    ]);
}

it('lists manual payments for admins with pending payments first', function () {
  $confirmed = makeManualPaymentForTest($this, [
    'status' => PaymentStatus::Confirmed->value
  ]);
  $pending = makeManualPaymentForTest($this);

  actingAs($this->admin)
    ->getJson(route('institutions.manual-payments.index', $this->institution))
    ->assertInertia(
      fn($page) => $page
        ->component('institutions/payments/list-manual-payments')
        ->where('manualPayments.data.0.id', $pending->id)
        ->where('manualPayments.data.1.id', $confirmed->id)
    );
});

it('allows accountants to confirm manual fee payments', function () {
  $manualPayment = makeManualPaymentForTest($this);

  actingAs($this->accountant)
    ->postJson(
      route('institutions.manual-payments.confirm', [
        $this->institution,
        $manualPayment
      ])
    )
    ->assertOk()
    ->assertJson(['success' => true]);

  $receipt = FeePaymentHandler::getReceipt($this->fee, $this->student);

  expect($manualPayment->fresh()->status)->toBe(PaymentStatus::Confirmed);
  expect($receipt->fresh()->amount_paid)->toBe($this->fee->amount);
  expect($this->institutionGroup->fresh()->credit_wallet)->toBe(
    $this->fee->amount
  );

  assertDatabaseHas('fee_payments', [
    'reference' => $manualPayment->reference,
    'receipt_id' => $receipt->id,
    'confirmed_by_user_id' => $this->accountant->id,
    'amount' => $this->fee->amount
  ]);
});

it('allows admins to reject manual payments', function () {
  $manualPayment = makeManualPaymentForTest($this);

  actingAs($this->admin)
    ->postJson(
      route('institutions.manual-payments.reject', [
        $this->institution,
        $manualPayment
      ]),
      ['review_note' => 'Could not find the transfer']
    )
    ->assertOk()
    ->assertJson(['success' => true]);

  assertDatabaseHas('manual_payments', [
    'id' => $manualPayment->id,
    'status' => PaymentStatus::Cancelled->value,
    'rejected_by_user_id' => $this->admin->id,
    'review_note' => 'Could not find the transfer'
  ]);
});

it('confirms manual admission form purchases', function () {
  $admissionForm = AdmissionForm::factory()->create([
    'institution_id' => $this->institution->id,
    'price' => 2500
  ]);
  $admissionApplication = AdmissionApplication::factory()
    ->admissionForm($admissionForm)
    ->create();
  $manualPayment = ManualPayment::factory()
    ->institution($this->institution)
    ->payable($admissionForm)
    ->paymentable($admissionApplication)
    ->create([
      'user_id' => null,
      'bank_account_id' => $this->bankAccount->id,
      'amount' => $admissionForm->price,
      'purpose' => PaymentPurpose::AdmissionFormPurchase->value,
      'meta' => [
        'admission_application_id' => $admissionApplication->id
      ]
    ]);

  actingAs($this->admin)
    ->postJson(
      route('institutions.manual-payments.confirm', [
        $this->institution,
        $manualPayment
      ])
    )
    ->assertOk()
    ->assertJson(['success' => true]);

  $purchase = AdmissionFormPurchase::query()
    ->where('reference', $manualPayment->reference)
    ->first();

  expect($manualPayment->fresh()->status)->toBe(PaymentStatus::Confirmed);
  expect($purchase)->not->toBeNull();
  expect($admissionApplication->fresh()->admission_form_purchase_id)->toBe(
    $purchase->id
  );
  expect($this->institutionGroup->fresh()->credit_wallet)->toBe(2500.0);
});

it('prevents non finance staff from confirming manual payments', function () {
  $manualPayment = makeManualPaymentForTest($this);

  actingAs($this->teacher)
    ->postJson(
      route('institutions.manual-payments.confirm', [
        $this->institution,
        $manualPayment
      ])
    )
    ->assertForbidden();
});

it('shows a user their manual payment history', function () {
  $manualPayment = makeManualPaymentForTest($this);
  makeManualPaymentForTest($this, ['user_id' => $this->accountant->id]);

  actingAs($this->student)
    ->getJson(route('institutions.manual-payments.history', $this->institution))
    ->assertInertia(
      fn($page) => $page
        ->component('institutions/payments/manual-payment-history')
        ->has('manualPayments.data', 1)
        ->where('manualPayments.data.0.id', $manualPayment->id)
    );
});

it('shows the pending manual payment page by reference', function () {
  $manualPayment = makeManualPaymentForTest($this, [
    'bank_account_id' => null
  ]);

  $this->get(
    route('institutions.manual-payments.show', [
      $this->institution->uuid,
      $manualPayment->reference
    ])
  )->assertInertia(
    fn($page) => $page
      ->component('institutions/payments/manual-payment-pending')
      ->where('manualPayment.id', $manualPayment->id)
      ->has('bankAccounts', 1)
  );
});

it('updates optional pending manual payment details', function () {
  Storage::fake('s3_public');
  $manualPayment = makeManualPaymentForTest($this, [
    'bank_account_id' => null,
    'depositor_name' => null,
    'proof_path' => null,
    'proof_url' => null,
    'paid_at' => null,
    'payload' => []
  ]);

  $this->post(
    route('institutions.manual-payments.pending.update', [
      $this->institution->uuid,
      $manualPayment->reference
    ]),
    [
      'bank_account_id' => $this->bankAccount->id,
      'depositor_name' => 'Jane Depositor',
      'paid_at' => now()->toDateString(),
      'note' => 'Transferred from mobile banking',
      'payment_proof' => UploadedFile::fake()->image('proof.jpg')
    ]
  )
    ->assertOk()
    ->assertJsonPath(
      'message',
      'Your manual payment details have been updated.'
    );

  assertDatabaseHas('manual_payments', [
    'id' => $manualPayment->id,
    'bank_account_id' => $this->bankAccount->id,
    'depositor_name' => 'Jane Depositor'
  ]);

  expect($manualPayment->fresh()->proof_path)->not->toBeNull();
  expect($manualPayment->fresh()->payload['note'])->toBe(
    'Transferred from mobile banking'
  );
});
