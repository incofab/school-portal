<?php

namespace App\Http\Controllers\Managers\BankAccounts;

use App\Http\Requests\StoreBankAccountRequest;
use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use Inertia\Inertia;

class BankAccountController extends Controller
{
  //
  public function index()
  {
    $user = currentUser();
    $bankAccounts = $user->partner
      ->bankAccounts()
      ->withCount('withdrawals')
      ->get();

    return Inertia::render('managers/bank-accounts/list-bank-accounts', [
      'bankAccounts' => $bankAccounts
    ]);
  }

  public function create()
  {
    return Inertia::render('managers/bank-accounts/create-edit-bank-account');
  }

  public function edit(BankAccount $bankAccount)
  {
    //= Bank account should not be editable if it has been used atleast once to make a withdrawal
    $this->authorize('update', $bankAccount);

    return Inertia::render('managers/bank-accounts/create-edit-bank-account', [
      'bankAccount' => $bankAccount
    ]);
  }

  public function update(
    BankAccount $bankAccount,
    StoreBankAccountRequest $request
  ) {
    //= Bank account should not be editable if it has been used atleast once to make a withdrawal
    $this->authorize('update', $bankAccount);

    $validated = $request->validated();
    $bankAccount->update($validated);
    return $this->ok();
  }

  public function store(StoreBankAccountRequest $request)
  {
    $validated = $request->validated();
    $user = currentUser();

    $partner = $user->partner;
    $accountableType = $partner->getMorphClass();
    $accountableId = $partner->id;

    BankAccount::create([
      ...collect($validated),
      'accountable_type' => $accountableType,
      'accountable_id' => $accountableId
    ]);

    return $this->ok();
  }

  public function destroy(BankAccount $bankAccount)
  {
    //= Bank account should not be deleteable if it has been used atleast once to make a withdrawal
    $this->authorize('delete', $bankAccount);

    $bankAccount->delete();
    return $this->ok();
  }
}
