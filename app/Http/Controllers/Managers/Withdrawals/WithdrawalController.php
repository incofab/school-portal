<?php

namespace App\Http\Controllers\Managers\Withdrawals;

use Illuminate\Validation\Rules\Enum;
use App\Http\Controllers\Controller;
use App\Enums\WithdrawalStatus;
use Illuminate\Http\Request;
use App\Models\Withdrawal;
use Inertia\Inertia;
use App\Enums\TransactionType;
use App\Models\InstitutionGroup;
use App\Models\Partner;
use App\Models\UserTransaction;
use App\Support\Fundings\RecordFunding;
use App\Support\MorphMap;

class WithdrawalController extends Controller
{
  //
  public function index()
  {
    $user = currentUser();

    if ($user->isAdmin()) {
      $bankAccounts = [];
      $withdrawals = Withdrawal::query()->with('bankAccount');
    } elseif ($user->isPartner()) {
      $bankAccounts = $user->partner->bankAccounts()->get();
      $withdrawals = $user->partner->withdrawals()->with('bankAccount');
    }

    return Inertia::render('managers/withdrawals/list-withdrawals', [
      'bankAccounts' => $bankAccounts,
      'withdrawals' => paginateFromRequest($withdrawals)
    ]);
  }

  public function store(Request $request)
  {
    $validated = $request->validate([
      'bank_account_id' => 'required|exists:bank_accounts,id',
      'amount' => 'required|numeric',
      'reference' => 'required|string'
    ]);

    $reqBankAccountId = $validated['bank_account_id'];
    $reqAmount = $validated['amount'];
    $reqReference = $validated['reference'];

    $user = currentUser();
    $partner = $user->partner;
    $currentFundBalance = floatval($partner->wallet);
    $newFundBalance = $currentFundBalance - $reqAmount;

    if ($newFundBalance < 0) {
      //Return Error - Insufficient Wallet Balance
      return $this->message('Insufficient Wallet Balance.', 401);
    }

    //= Deduct balance
    $partner->update([
      'wallet' => $newFundBalance
    ]);

    //= Save to Withdrawals DB Table
    $withdrawal = Withdrawal::create([
      'bank_account_id' => $reqBankAccountId,
      'withdrawable_type' => $partner->getMorphClass(),
      'withdrawable_id' => $partner->id,
      'amount' => $reqAmount,
      'status' => WithdrawalStatus::Pending->value,
      'reference' => $reqReference
    ]);

    //= Save to Transactions Table
    UserTransaction::Create([
      'type' => TransactionType::Debit,
      'amount' => $reqAmount,
      'bbt' => $currentFundBalance,
      'bat' => $newFundBalance,
      'entity_type' => $partner->getMorphClass(),
      'entity_id' => $partner->id,
      'transactionable_type' => $withdrawal->getMorphClass(),
      'transactionable_id' => $withdrawal->id,
      'reference' => $reqReference,
      'remark' => null
    ]);

    return $this->ok();
  }

  public function update(Withdrawal $withdrawal, Request $request)
  {
    $request->validate([
      'status' => ['required', new Enum(WithdrawalStatus::class)],
      'remark' => ['nullable', 'string']
    ]);

    $reqStatus = $request->status;
    $reqRemark = $request->remark ?? null;
    $withdrawalAmount = floatval($withdrawal->amount);
    $withdrawalReference = $withdrawal->reference;

    //= If the Status is DECLINED, refund the user.
    if ($reqStatus === WithdrawalStatus::Declined->value) {
      $withdrawableType = $withdrawal->withdrawable_type;
      $withdrawableId = $withdrawal->withdrawable_id;

      //= InstitutionGroup
      if ($withdrawableType === MorphMap::key(InstitutionGroup::class)) {
        $institutionGroup = InstitutionGroup::find($withdrawableId);

        //Refund the User and Add record to Transaction DB Table
        RecordFunding::make(
          $institutionGroup,
          currentUser()
        )->recordCreditTopup(
          $withdrawalAmount,
          $withdrawalReference,
          $withdrawal,
          null
        );
      }

      //= Partner
      if ($withdrawableType === MorphMap::key(Partner::class)) {
        $partner = Partner::find($withdrawableId);
        $currentBalance = floatval($partner->wallet);
        $newBalance = $currentBalance + $withdrawalAmount;

        $partner->update([
          'wallet' => $newBalance
        ]);

        //Add record to UserTransaction DB Table
        UserTransaction::Create([
          'type' => TransactionType::Credit,
          'amount' => $withdrawalAmount,
          'bbt' => $currentBalance,
          'bat' => $newBalance,
          'entity_type' => $partner->getMorphClass(),
          'entity_id' => $partner->id,
          'transactionable_type' => $withdrawal->getMorphClass(),
          'transactionable_id' => $withdrawal->id,
          'reference' => $withdrawalReference,
          'remark' => null
        ]);
      }
    }

    $withdrawal->update([
      'status' => $reqStatus,
      'remark' => $reqRemark,
      'paid_at' => now()
    ]);

    return $this->ok();
  }
}
