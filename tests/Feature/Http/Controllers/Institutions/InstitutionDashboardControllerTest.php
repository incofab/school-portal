<?php

use App\Enums\Payments\PaymentPurpose;
use App\Enums\Payments\PaymentStatus;
use App\Models\BankAccount;
use App\Models\Fee;
use App\Models\Institution;
use App\Models\ManualPayment;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;

beforeEach(function () {
  $this->institution = Institution::factory()->create();
  $this->institutionGroup = $this->institution->institutionGroup;
  $this->admin = $this->institution->createdBy;
  $this->accountant = User::factory()
    ->accountant($this->institution)
    ->create();
  $this->student = User::factory()
    ->student($this->institution)
    ->create();
  $this->fee = Fee::factory()
    ->institution($this->institution)
    ->create();
});

function makeDashboardManualPayment($test, array $attributes = []): ManualPayment
{
  return ManualPayment::factory()
    ->institution($test->institution)
    ->payable($test->student)
    ->paymentable($test->fee)
    ->create([
      'user_id' => $test->student->id,
      'amount' => $test->fee->amount,
      'purpose' => PaymentPurpose::Fee->value,
      ...$attributes
    ]);
}

it('shows dashboard attention summary for institution admins', function () {
  makeDashboardManualPayment($this);
  makeDashboardManualPayment($this, [
    'status' => PaymentStatus::Confirmed->value
  ]);

  actingAs($this->admin)
    ->get(route('institutions.dashboard', $this->institution))
    ->assertInertia(function (AssertableInertia $page) {
      $page
        ->component('institutions/dashboard')
        ->where('attentionSummary.pendingManualPaymentsCount', 1)
        ->where('attentionSummary.unreadChatCount', 0)
        ->where('attentionSummary.hasBankAccounts', false)
        ->where('attentionSummary.canManageBankAccounts', true);
    });
});

it('shows dashboard attention summary for accountants', function () {
  BankAccount::factory()
    ->accountable($this->institutionGroup)
    ->create();
  makeDashboardManualPayment($this);

  actingAs($this->accountant)
    ->get(route('institutions.dashboard', $this->institution))
    ->assertInertia(function (AssertableInertia $page) {
      $page
        ->component('institutions/dashboard')
        ->where('attentionSummary.pendingManualPaymentsCount', 1)
        ->where('attentionSummary.unreadChatCount', 0)
        ->where('attentionSummary.hasBankAccounts', true)
        ->where('attentionSummary.canManageBankAccounts', false);
    });
});

it('does not expose dashboard attention summary to students', function () {
  makeDashboardManualPayment($this);

  actingAs($this->student)
    ->get(route('institutions.dashboard', $this->institution))
    ->assertInertia(function (AssertableInertia $page) {
      $page
        ->component('institutions/dashboard')
        ->where('attentionSummary', null);
    });
});
