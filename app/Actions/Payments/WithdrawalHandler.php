<?php
namespace App\Actions\Payments;

use App\Enums\WithdrawalStatus;
use App\Models\BankAccount;
use App\Models\Institution;
use App\Models\InstitutionGroup;
use App\Models\Partner;
use App\Models\User;
use App\Models\Withdrawal;
use App\Support\CommissionHandler;
use App\Support\Fundings\RecordFunding;
use App\Support\Res;
use DB;
use Illuminate\Database\Eloquent\Model;

class WithdrawalHandler
{
  static function make()
  {
    return new self();
  }

  function confirmWithdrawal(Withdrawal $withdrawal, ?User $user, $remark = '')
  {
    $withdrawal->markAsProcessed($user, WithdrawalStatus::Paid, $remark);
  }

  function declineWithdrawal(Withdrawal $withdrawal, ?User $user, $remark = '')
  {
    //= If the Status is DECLINED, refund the user.
    $withdrawable = $withdrawal->withdrawable;
    if ($withdrawable instanceof InstitutionGroup) {
      //Refund the User and Add record to Transaction DB Table
      RecordFunding::make($withdrawable, $user)->recordCreditTopup(
        $withdrawal->amount,
        $withdrawal->reference,
        $withdrawal,
        $remark
      );
    }

    //= Partner
    if ($withdrawable instanceof Partner) {
      //= Refund the Partner, and save record to UserTransaction DB Table
      CommissionHandler::make($withdrawable->reference)->refundPartner(
        $withdrawable,
        $withdrawal,
        $remark
      );
    }
    $withdrawal->markAsProcessed($user, WithdrawalStatus::Declined, $remark);
  }

  function recordPartnerWithdrawal(
    Partner $partner,
    BankAccount $bankAccount,
    float $amount,
    string $reference
  ): Res {
    if ($partner->wallet < $amount) {
      return failRes('Insufficient Wallet Balance.');
    }
    DB::beginTransaction();
    $withdrawal = $this->createWithdrawal(
      $bankAccount,
      $partner,
      $amount,
      $reference
    );

    //= Deduct Balance, Save to Withdrawals DB Table, and Save to Transactions DB Table
    CommissionHandler::make($reference)->debitPartner(
      $partner,
      $amount,
      $withdrawal
    );
    DB::commit();
    return successRes();
  }

  function recordInstitutionWithdrawal(
    InstitutionGroup $institutionGroup,
    BankAccount $bankAccount,
    User $user,
    float $amount,
    string $reference,
    ?Institution $institution = null
  ): Res {
    if ($institutionGroup->credit_wallet < $amount) {
      return failRes('Insufficient Wallet Balance.');
    }

    DB::beginTransaction();
    $withdrawal = $this->createWithdrawal(
      $bankAccount,
      $institutionGroup,
      $amount,
      $reference
    );

    //= Deduct the InstGroup -AND- Save record to Transactions Table
    RecordFunding::make($institutionGroup, $user)->recordCreditDeduction(
      $amount,
      $reference,
      $withdrawal,
      null
    );
    DB::commit();
    return successRes();
  }

  private function createWithdrawal(
    BankAccount $bankAccount,
    Model $withdrawable,
    float $amount,
    string $reference
  ) {
    return Withdrawal::create([
      'bank_account_id' => $bankAccount->id,
      'withdrawable_type' => $withdrawable->getMorphClass(),
      'withdrawable_id' => $withdrawable->id,
      'amount' => $amount,
      'status' => WithdrawalStatus::Pending->value,
      'reference' => $reference
    ]);
  }
}
