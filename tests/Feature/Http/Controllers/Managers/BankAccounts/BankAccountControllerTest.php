<?php

use App\Enums\PartnerUserRole;
use App\Models\BankAccount;
use App\Models\Partner;
use App\Models\User;

use function Pest\Laravel\actingAs;

it(
  'prevents partner staff from adding or updating partner bank accounts',
  function () {
    $partner = Partner::factory()->create();
    $staff = User::factory()
      ->partnerManager()
      ->create();
    $partner->partnerUsers()->create([
      'user_id' => $staff->id,
      'role' => PartnerUserRole::Staff->value
    ]);
    $bankAccount = BankAccount::factory()
      ->accountable($partner)
      ->create();

    actingAs($staff)
      ->get(route('managers.bank-accounts.create'))
      ->assertForbidden();

    actingAs($staff)
      ->put(route('managers.bank-accounts.update', [$bankAccount]), [
        'bank_name' => 'Example Bank',
        'bank_code' => '001',
        'account_name' => 'Partner Account',
        'account_number' => '0123456789'
      ])
      ->assertForbidden();
  }
);
