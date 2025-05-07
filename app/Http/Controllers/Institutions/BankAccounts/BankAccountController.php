<?php

namespace App\Http\Controllers\Institutions\BankAccounts;

use App\Http\Requests\StoreBankAccountRequest;
use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\Institution;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BankAccountController extends Controller
{
  //
  public function index(Institution $institution)
  {
    $bankAccounts = $institution->institutionGroup->bankAccounts()->get();

    return Inertia::render('institutions/bank-accounts/list-bank-accounts', [
      'bankAccounts' => $bankAccounts
    ]);
  }

  public function create()
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
    $bankAccount->update([...collect($validated)->except('institution_id')]);
    return $this->ok();
  }

  public function store(
    Institution $institution,
    StoreBankAccountRequest $request
  ) {
    $validated = $request->validated();
    $institutionGroup = $institution->institutionGroup;
    $accountableType = $institutionGroup->getMorphClass();
    $accountableId = $institutionGroup->id;

    BankAccount::create([
      ...collect($validated)->except('institution_id'),
      'accountable_type' => $accountableType,
      'accountable_id' => $accountableId
    ]);

    return $this->ok();
  }

  public function destroy(Institution $institution, BankAccount $bankAccount)
  {
    $bankAccount->delete();
    return $this->ok();
  }
}
