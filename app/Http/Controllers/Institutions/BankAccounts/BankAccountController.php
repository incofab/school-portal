<?php

namespace App\Http\Controllers\Institutions\BankAccounts;

use App\Http\Requests\StoreBankAccountRequest;
use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\Institution;
use App\Support\BankAccountHandler;
use Inertia\Inertia;

class BankAccountController extends Controller
{
  public function __construct()
  {
    $this->onlyAdmins()->except('index');
  }

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

  public function edit(Institution $institution, BankAccount $instBankAccount)
  {
    return Inertia::render(
      'institutions/bank-accounts/create-edit-bank-account',
      [
        'bankAccount' => $instBankAccount
      ]
    );
  }

  public function update(
    Institution $institution,
    BankAccount $instBankAccount,
    StoreBankAccountRequest $request
  ) {
    $validated = $request->validated();
    BankAccountHandler::make($institution->institutionGroup)->update(
      $instBankAccount,
      collect($validated)
        ->except('institution_id')
        ->toArray()
    );
    return $this->ok();
  }

  public function store(
    Institution $institution,
    StoreBankAccountRequest $request
  ) {
    $validated = $request->validated();
    BankAccountHandler::make($institution->institutionGroup)->store(
      collect($validated)
        ->except('institution_id')
        ->toArray()
    );

    return $this->ok();
  }

  public function destroy(
    Institution $institution,
    BankAccount $instBankAccount
  ) {
    BankAccountHandler::make($institution->institutionGroup)->destroy(
      $instBankAccount
    );
    return $this->ok();
  }
}
