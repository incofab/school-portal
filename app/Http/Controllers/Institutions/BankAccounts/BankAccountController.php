<?php

namespace App\Http\Controllers\Institutions\BankAccounts;

use App\Http\Requests\StoreBankAccountRequest;
use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\Institution;
use Inertia\Inertia;

class BankAccountController extends Controller
{
  //
  public function index(Institution $institution)
  {
    $bankAccounts = $institution->institutionGroup
      ->bankAccounts()
      ->withCount('withdrawals')
      ->get();

    return Inertia::render('institutions/bank-accounts/list-bank-accounts', [
      'bankAccounts' => $bankAccounts
    ]);
  }

  public function create(Institution $institution)
  {
    return Inertia::render(
      'institutions/bank-accounts/create-edit-bank-account'
    );
  }

  public function edit(Institution $institution, BankAccount $bankAccount)
  {
    return Inertia::render(
      'institutions/bank-accounts/create-edit-bank-account',
      [
        'bankAccount' => $bankAccount
      ]
    );
  }

  public function update(
    Institution $institution,
    BankAccount $bankAccount,
    StoreBankAccountRequest $request
  ) {
    $validated = $request->validated();

    if ($request->is_primary) {
      $institution->institutionGroup
        ->bankAccounts()
        ->where('is_primary', true)
        ->where('id', '!=', $bankAccount->id)
        ->update(['is_primary' => false]);
    }
    $bankAccount->update([...collect($validated)->except('institution_id')]);
    return $this->ok();
  }

  public function store(
    Institution $institution,
    StoreBankAccountRequest $request
  ) {
    $validated = $request->validated();
    $institutionGroup = $institution->institutionGroup;

    if ($request->is_primary) {
      $institutionGroup
        ->bankAccounts()
        ->where('is_primary', true)
        ->update(['is_primary' => false]);
    }
    BankAccount::create([
      ...collect($validated)->except('institution_id'),
      'accountable_type' => $institutionGroup->getMorphClass(),
      'accountable_id' => $institutionGroup->id
    ]);

    return $this->ok();
  }

  public function destroy(Institution $institution, BankAccount $bankAccount)
  {
    $bankAccount->delete();
    return $this->ok();
  }
}
