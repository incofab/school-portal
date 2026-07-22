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
use Illuminate\Support\Facades\DB;

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
      $this->institutionGroup = $institutionOrGroup->institutionGroup;
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
    return $this->recordTransaction();
  }

  private function recordTransaction()
  {
    return DB::transaction(function () {
      $this->institutionGroup = $this->institutionGroup->freshWithLockForUpdate();

      $existingTransaction = Transaction::query()
        ->where('reference', $this->reference)
        ->first();

      if ($existingTransaction) {
        return $existingTransaction;
      }

      if ($this->amount < 1) {
        return throw new Exception('Amount cannot be zero or less');
      }

      $walletColumn =
        $this->walletType === WalletType::Debt
          ? 'debt_wallet'
          : 'credit_wallet';

      $this->bbt = $this->institutionGroup->{$walletColumn};
      $this->bat =
        $this->transactionType === TransactionType::Credit
          ? $this->bbt + $this->amount
          : $this->bbt - $this->amount;

      if ($this->bat < 0) {
        return throw new Exception('Wallet balance cannot be negative');
      }

      $this->institutionGroup->fill([$walletColumn => $this->bat])->save();

      return Transaction::query()->create([
        'reference' => $this->reference,
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
      ]);
    });
  }
}
