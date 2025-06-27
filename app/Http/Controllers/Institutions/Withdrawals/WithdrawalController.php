<?php

namespace App\Http\Controllers\Institutions\Withdrawals;

use App\Actions\Payments\WithdrawalHandler;
use App\Http\Controllers\Controller;
use App\Models\Institution;
use Inertia\Inertia;
use Illuminate\Http\Request;

class WithdrawalController extends Controller
{
  //
  public function index(Institution $institution)
  {
    $bankAccounts = $institution->institutionGroup->bankAccounts()->get();
    $withdrawals = $institution->institutionGroup
      ->withdrawals()
      ->with('bankAccount');

    return Inertia::render('institutions/withdrawals/list-withdrawals', [
      'bankAccounts' => $bankAccounts,
      'withdrawals' => paginateFromRequest($withdrawals)
    ]);
  }

  public function store(Institution $institution, Request $request)
  {
    $validated = $request->validate([
      'bank_account_id' => 'required|exists:bank_accounts,id',
      'amount' => 'required|numeric',
      'reference' => 'required|string'
    ]);

    $institutionGroup = $institution->institutionGroup;
    $bankAccount = $institutionGroup
      ->bankAccounts()
      ->where('id', $validated['bank_account_id'])
      ->firstOrFail();
    $res = WithdrawalHandler::make()->recordInstitutionWithdrawal(
      $institutionGroup,
      $bankAccount,
      currentUser(),
      $validated['amount'],
      $validated['reference']
    );

    return $res->isSuccessful()
      ? $this->ok()
      : $this->message($res->getMessage(), 401);
  }
}
