<?php

namespace App\Http\Controllers\Institutions\Withdrawals;

use App\Http\Controllers\Controller;
use App\Enums\WithdrawalStatus;
use App\Models\Institution;
use App\Models\Withdrawal;
use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Support\Fundings\RecordFunding;

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

    $reqBankAccountId = $validated['bank_account_id'];
    $reqAmount = $validated['amount'];
    $reqReference = $validated['reference'];

    $institutionGroup = $institution->institutionGroup;
    $currentFundBalance = floatval($institutionGroup->credit_wallet);
    $newFundBalance = $currentFundBalance - $reqAmount;

    if ($newFundBalance < 0) {
      //Return Error - Insufficient Wallet Balance
      return $this->message('Insufficient Wallet Balance.', 401);
    }

    //= Save to Withdrawals DB Table
    $withdrawal = Withdrawal::create([
      'bank_account_id' => $reqBankAccountId,
      'withdrawable_type' => $institutionGroup->getMorphClass(),
      'withdrawable_id' => $institutionGroup->id,
      'amount' => $reqAmount,
      'status' => WithdrawalStatus::Pending->value,
      'reference' => $reqReference
    ]);

    //= Deduct the InstGroup -AND- Save record to Transactions Table
    RecordFunding::make(
      $institutionGroup,
      currentUser()
    )->recordCreditDeduction($reqAmount, $reqReference, $withdrawal, null);

    return $this->ok();
  }
}
