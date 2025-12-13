<?php

namespace App\Http\Controllers\Institutions\BankAccounts;

use App\Http\Requests\StoreBankAccountRequest;
use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\Institution;
use App\Support\BankAccountHandler;
use Inertia\Inertia;

class InstitutionUserBankAccountController extends Controller
{
  public function index(Institution $institution)
  {
    $bankAccounts = currentInstitutionUser()
      ->bankAccounts()
      ->withCount('withdrawals')
      ->get();

    return Inertia::render(
      'institutions/bank-accounts/institution-users/list-bank-accounts',
      [
        'bankAccounts' => $bankAccounts
      ]
    );
  }

  public function create(Institution $institution)
  {
    return Inertia::render(
      'institutions/bank-accounts/institution-users/create-edit-bank-account'
    );
  }

  public function edit(
    Institution $institution,
    BankAccount $instUserBankAccount
  ) {
    return Inertia::render(
      'institutions/bank-accounts/institution-users/create-edit-bank-account',
      [
        'bankAccount' => $instUserBankAccount
      ]
    );
  }

  public function update(
    Institution $institution,
    BankAccount $instUserBankAccount,
    StoreBankAccountRequest $request
  ) {
    $validated = $request->validated();
    BankAccountHandler::make(currentInstitutionUser())->update(
      $instUserBankAccount,
      $validated
    );
    return $this->ok();
  }

  public function store(
    Institution $institution,
    StoreBankAccountRequest $request
  ) {
    $validated = $request->validated();
    BankAccountHandler::make(currentInstitutionUser())->store($validated);
    return $this->ok();
  }

  public function destroy(
    Institution $institution,
    BankAccount $instUserBankAccount
  ) {
    BankAccountHandler::make(currentInstitutionUser())->destroy(
      $instUserBankAccount
    );
    return $this->ok();
  }
}
