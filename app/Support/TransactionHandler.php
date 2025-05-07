<?php
namespace App\Support;

use App\Enums\TransactionType;
use App\Enums\WalletType;
use App\Models\Institution;
use App\Models\InstitutionGroup;
use App\Models\PaymentReference;
use App\Models\Transaction;
use Exception;
use Illuminate\Database\Eloquent\Model;

class TransactionHandler
{
  private float $amount;
  private float $bbt;
  private float $bat;
  private WalletType $walletType;
  private TransactionType $transactionType;
  private Model $transactionable;
  private ?string $remark;
  private ?Institution $institution = null;
  private InstitutionGroup $institutionGroup;

  function __construct(
    Institution|InstitutionGroup $institutionOrGroup,
    private string $reference
  ) {
    if ($institutionOrGroup instanceof Institution) {
      // Fresh to be sure we're using the DB updated version
      $this->institutionGroup = $institutionOrGroup->institutionGroup->fresh();
      $this->institution = $institutionOrGroup;
    } else {
      $this->institutionGroup = $institutionOrGroup;
    }
  }

  static function make(
    Institution|InstitutionGroup $institutionOrGroup,
    string $reference
  ) {
    return new self($institutionOrGroup, $reference);
  }

  static function makeFromPaymentReference(PaymentReference $paymentReference)
  {
    return new self(
      $paymentReference->institution,
      $paymentReference->reference
    ); 
  }

  function topupDebtWallet(
    $amount,
    Model $transactionable,
    ?string $remark = null
  ) {
    $this->remark = $remark;
    $this->walletType = WalletType::Debt;
    $this->transactionType = TransactionType::Credit;
    $this->transactionable = $transactionable;

    $this->amount = $amount;
    $this->bbt = $this->institutionGroup->debt_wallet;
    $this->bat = $this->bbt + $this->amount;
    $this->recordTransaction();
  }

  function deductDebtWallet(
    float $amount,
    Model $transactionable,
    ?string $remark = null
  ) {
    $this->remark = $remark;
    $this->walletType = WalletType::Debt;
    $this->transactionType = TransactionType::Debit;
    $this->transactionable = $transactionable;

    $this->amount = $amount;
    $this->bbt = $this->institutionGroup->debt_wallet;
    $this->bat = $this->bbt - $this->amount;
    $this->recordTransaction();
  }

  function topupCreditWallet(
    $amount,
    Model $transactionable,
    ?string $remark = null
  ) {
    $this->remark = $remark;
    $this->walletType = WalletType::Credit;
    $this->transactionType = TransactionType::Credit;
    $this->transactionable = $transactionable;

    $this->amount = $amount;
    $this->bbt = $this->institutionGroup->credit_wallet;
    $this->bat = $this->bbt + $this->amount;
    $this->recordTransaction();
  }

  function deductCreditWallet(
    $amount,
    Model $transactionable,
    ?string $remark = null
  ) {
    $this->remark = $remark;
    $this->walletType = WalletType::Credit;
    $this->transactionType = TransactionType::Debit;
    $this->transactionable = $transactionable;

    $this->amount = $amount;
    $this->bbt = $this->institutionGroup->credit_wallet;
    $this->bat = $this->bbt - $this->amount;
    return $this->recordTransaction();
  }

  private function recordTransaction()
  {
    if ($this->amount < 1) {
      return throw new Exception('Amount cannot be zero or less');
    }
    if ($this->bat < 0) {
      return throw new Exception('Wallet balance cannot be negative');
    }

    if ($this->walletType === WalletType::Debt) {
      $this->institutionGroup->fill(['debt_wallet' => $this->bat])->save();
    } else {
      $this->institutionGroup->fill(['credit_wallet' => $this->bat])->save();
    }

    $dTransaction = Transaction::query()->firstOrCreate(
      ['reference' => $this->reference],
      [
        'institution_group_id' => $this->institutionGroup->id,
        'institution_id' => $this->institution?->id,
        'wallet' => $this->walletType,
        'amount' => $this->amount,
        'type' => $this->transactionType,
        'bbt' => $this->bbt,
        'bat' => $this->bat,
        'transactionable_type' => $this->transactionable->getMorphClass(),
        'transactionable_id' => $this->transactionable->id,
        'remark' => $this->remark
      ]
    );

    return $dTransaction;
  }
}
