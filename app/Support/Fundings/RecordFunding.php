<?php

namespace App\Support\Fundings;

use App\Enums\WalletType;
use App\Models\Funding;
use App\Models\InstitutionGroup;
use App\Models\User;
use App\Support\TransactionHandler;
use Exception;
use Illuminate\Database\Eloquent\Model;

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
    $funding = $this->recordFunding($reference);

    TransactionHandler::make(
      $this->institutionGroup,
      $reference
    )->topupDebtWallet($amount, $funding, $remark);
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
    $funding = $this->recordFunding($reference);

    TransactionHandler::make(
      $this->institutionGroup,
      $reference
    )->deductDebtWallet($amount, $funding, $remark);
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
    $funding = $this->recordFunding($reference);

    TransactionHandler::make(
      $this->institutionGroup,
      $reference
    )->topupCreditWallet($amount, $funding, $remark);
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
    $funding = $this->recordFunding($reference);

    TransactionHandler::make(
      $this->institutionGroup,
      $reference
    )->deductCreditWallet($amount, $funding, $remark);
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
