<?php

namespace App\Http\Controllers\Managers\BankAccounts;

use App\Http\Requests\StoreBankAccountRequest;
use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\Partner;
use App\Support\BankAccountHandler;
use App\Support\MorphMap;
use Inertia\Inertia;

class BankAccountController extends Controller
{
  //
  public function index()
  {
    $user = currentUser();
    $bankAccounts = $user->isAdmin()
      ? BankAccount::query()
        ->where('accountable_type', MorphMap::key(Partner::class))
        ->get()
      : $user->partner
        ?->bankAccounts()
        ->withCount('validWithdrawals')
        ->get();

    return Inertia::render('managers/bank-accounts/list-bank-accounts', [
      'bankAccounts' => $bankAccounts ?? []
    ]);
  }

  public function create()
  {
    return Inertia::render('managers/bank-accounts/create-edit-bank-account');
  }

  public function edit(BankAccount $bankAccount)
  {
    return Inertia::render('managers/bank-accounts/create-edit-bank-account', [
      'bankAccount' => $bankAccount
    ]);
  }

  public function update(
    BankAccount $bankAccount,
    StoreBankAccountRequest $request
  ) {
    $validated = $request->validated();
    BankAccountHandler::make(currentUser()->partner)->update(
      $bankAccount,
      $validated
    );
    return $this->ok();
  }

  public function store(StoreBankAccountRequest $request)
  {
    $validated = $request->validated();
    BankAccountHandler::make(currentUser()->partner)->store($validated);

    return $this->ok();
  }

  public function destroy(BankAccount $bankAccount)
  {
    BankAccountHandler::make(currentUser()->partner)->destroy($bankAccount);
    return $this->ok();
  }
}
