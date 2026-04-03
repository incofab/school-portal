<?php

namespace App\Support\Fundings;

use App\Enums\TransactionType;
use App\Enums\WalletType;
use App\Models\Funding;
use App\Models\InstitutionGroup;
use App\Models\User;
use App\Support\TransactionHandler;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class RecordFunding
{
  private float $amount;
  private ?string $remark = '';

  private float $bbt;
  private float $bat;
  private ?Model $fundable = null;
  private WalletType $walletType;

  function __construct(
    private InstitutionGroup $institutionGroup,
    private User $user
  ) {
  }

  static function make(InstitutionGroup $institutionGroup, User $user)
  {
    return new self($institutionGroup, $user);
  }

  function revertFunding(Funding $funding): void
  {
    $remark = 'Reverted: ' . $funding->remark;
    $reference = $funding->revertReference();
    $transaction = $funding->transaction;
    $amount = $transaction->amount;
    abort_unless($transaction, 404, 'Transaction record not found');

    if ($funding->wallet == WalletType::Credit) {
      if ($transaction->type === TransactionType::Credit) {
        $this->recordCreditDeduction($amount, $reference, $funding, $remark);
      } else {
        $this->recordCreditTopup($amount, $reference, $funding, $remark);
      }
    } else {
      if ($transaction->type === TransactionType::Credit) {
        $this->recordDebtReduction($amount, $reference, $funding, $remark);
      } else {
        $this->recordDebtTopup($amount, $reference, $funding, $remark);
      }
    }
  }

  function deductWallet(string $walletType, $amount, string $reference, $remark)
  {
    if ($walletType == WalletType::Debt) {
      $this->recordDebtReduction($amount, $reference, null, $remark);
    } else {
      $this->recordCreditDeduction($amount, $reference, null, $remark);
    }
  }

  function recordDebtTopup(
    $amount,
    string $reference,
    ?Model $fundable = null,
    $remark = ''
  ) {
    $this->walletType = WalletType::Debt;
    $this->fundable = $fundable;
    $this->remark = $remark;
    $this->amount = $amount;

    $this->bbt = $this->institutionGroup->debt_wallet;
    $this->bat = $this->bbt + $this->amount;
    DB::beginTransaction();
    $funding = $this->recordFunding($reference);

    TransactionHandler::make(
      $this->institutionGroup,
      $reference
    )->topupDebtWallet($amount, $funding, $remark);
    DB::commit();
  }

  function recordDebtReduction(
    $amount,
    string $reference,
    ?Model $fundable = null,
    $remark = ''
  ) {
    $this->walletType = WalletType::Debt;
    $this->fundable = $fundable;
    $this->remark = $remark;
    $this->amount = $amount;

    $this->bbt = $this->institutionGroup->debt_wallet;
    $this->bat = $this->bbt - $this->amount;

    DB::beginTransaction();
    $funding = $this->recordFunding($reference);

    TransactionHandler::make(
      $this->institutionGroup,
      $reference
    )->deductDebtWallet($amount, $funding, $remark);
    DB::commit();
  }

  function recordCreditTopup(
    $amount,
    string $reference,
    ?Model $fundable = null,
    $remark = ''
  ) {
    $this->walletType = WalletType::Credit;
    $this->fundable = $fundable;
    $this->remark = $remark;
    $this->amount = $amount;

    $this->bbt = $this->institutionGroup->credit_wallet;
    $this->bat = $this->bbt + $this->amount;

    DB::beginTransaction();
    $funding = $this->recordFunding($reference);

    TransactionHandler::make(
      $this->institutionGroup,
      $reference
    )->topupCreditWallet($amount, $funding, $remark);
    DB::commit();
  }

  function recordCreditDeduction(
    $amount,
    string $reference,
    ?Model $fundable = null,
    $remark = ''
  ) {
    $this->walletType = WalletType::Credit;
    $this->fundable = $fundable;
    $this->remark = $remark;
    $this->amount = $amount;

    $this->bbt = $this->institutionGroup->credit_wallet;
    $this->bat = $this->bbt - $this->amount;

    DB::beginTransaction();
    $funding = $this->recordFunding($reference);

    TransactionHandler::make(
      $this->institutionGroup,
      $reference
    )->deductCreditWallet($amount, $funding, $remark);
    DB::commit();
  }

  private function recordFunding(string $reference): Funding
  {
    if ($this->bat < 0) {
      return throw new Exception('Wallet balance cannot be negative');
    }
    return $this->institutionGroup->fundings()->firstOrCreate(
      ['reference' => $reference],
      [
        'amount' => $this->amount,
        'wallet' => $this->walletType,
        'previous_balance' => $this->bbt,
        'new_balance' => $this->bat,

        'funded_by_user_id' => $this->user->id,
        'remark' => $this->remark,
        'fundable_id' => $this->fundable?->id,
        'fundable_type' => $this->fundable?->getMorphClass()
      ]
    );
  }
}
