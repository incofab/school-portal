<?php

namespace App\Support\Fundings;

use App\Enums\TransactionType;
use App\Enums\WalletType;
use App\Models\Funding;
use App\Models\InstitutionGroup;
use App\Models\PaymentReference;
use App\Models\Transaction;
use App\Models\User;
use DB;
use Illuminate\Database\Eloquent\Model;

class FundingHandler
{
  private $principalAmount;
  private $reference;
  private $debtReference;
  private $creditReference;

  /**
   * @param array{
   *  amount: int|float,
   *  reference: string,
   *  remark: string
   * } $data
   */
  function __construct(
    private InstitutionGroup $institutionGroup,
    private User $user,
    private array $data
  ) {
    $this->principalAmount = $data['amount'];
    $this->reference = $data['reference'];
    $this->debtReference = Funding::debtReference($data['reference']);
    $this->creditReference = Funding::creditReference($data['reference']);
  }

  static function makeFromPaymentRef(PaymentReference $paymentRef): static
  {
    return new self(
      $paymentRef->institution->institutionGroup,
      $paymentRef->user,
      [
        'reference' => $paymentRef->reference,
        'amount' => $paymentRef->amount
      ]
    );
  }

  function run(WalletType $fundingType, ?Model $fundable = null)
  {
    if ($fundingType === WalletType::Debt) {
      return $this->requestDebt($fundable);
    } else {
      return $this->processWalletPayment($fundable);
    }
  }

  function requestDebt(?Model $fundable = null)
  {
    return $this->giveLoan($this->principalAmount, $fundable);
  }

  function processWalletPayment(?Model $fundable)
  {
    $surplus = $this->payDebt($this->principalAmount);
    if ($surplus > 0) {
      return $this->fundCreditWallet(
        $surplus,
        TransactionType::Credit,
        $fundable
      );
    }
    return failRes('Not enough amount remaining after debt');
  }

  function giveLoan($amount, ?Model $fundable = null)
  {
    $this->fundDebtWallet($amount, TransactionType::Credit);
    $this->fundCreditWallet($amount, TransactionType::Credit, $fundable);
    return successRes('Loan given');
  }

  function payDebt($amount)
  {
    if (!$this->institutionGroup->isOwing()) {
      return $amount;
    }
    $prevDebtBal = $this->institutionGroup->debt_wallet;

    $debtToPay = $amount > $prevDebtBal ? $prevDebtBal : $amount;
    $this->fundDebtWallet($debtToPay, TransactionType::Debit);
    return $amount - $prevDebtBal;
  }

  function fundCreditWallet(
    $amount,
    TransactionType $type,
    ?Model $fundable = null
  ) {
    $prevCreditBal = $this->institutionGroup->credit_wallet;
    $newCreditBal =
      $type === TransactionType::Credit
        ? $amount + $prevCreditBal
        : $amount - $prevCreditBal;

    DB::beginTransaction();
    $this->institutionGroup->fill(['credit_wallet' => $newCreditBal])->save();

    $funding = $this->institutionGroup->fundings()->firstOrCreate(
      ['reference' => $this->creditReference],
      [
        'amount' => $amount,
        'wallet' => WalletType::Credit->value,
        'previous_balance' => $prevCreditBal,
        'new_balance' => $newCreditBal,

        'funded_by_user_id' => $this->user->id,
        'remark' => $this->data['remark'] ?? '',
        'fundable_id' => $fundable?->id,
        'fundable_type' => $fundable?->getMorphClass()
      ]
    );
    Transaction::record(
      $this->institutionGroup,
      $this->creditReference,
      WalletType::Credit,
      $amount,
      $type,
      $prevCreditBal,
      $newCreditBal,
      $funding
    );
    DB::commit();
    return successRes('Wallet funded successfully');
  }

  function fundDebtWallet($amount, TransactionType $type)
  {
    $prevDebtBal = $this->institutionGroup->debt_wallet;
    $newDebtBal =
      $type === TransactionType::Credit
        ? $amount + $prevDebtBal
        : $amount - $prevDebtBal;

    DB::beginTransaction();
    $funding = $this->institutionGroup->fundings()->firstOrCreate(
      [
        'reference' => $this->debtReference
      ],
      [
        'amount' => $amount,
        'previous_balance' => $prevDebtBal,
        'new_balance' => $newDebtBal,

        'wallet' => WalletType::Debt->value,
        'remark' => $this->data['remark'] ?? '',
        'funded_by_user_id' => $this->user->id
      ]
    );

    $this->institutionGroup->fill(['debt_wallet' => $newDebtBal])->save();

    Transaction::record(
      $this->institutionGroup,
      $this->debtReference,
      WalletType::Debt,
      $amount,
      $type,
      $prevDebtBal,
      $newDebtBal,
      $funding
    );
    DB::commit();
  }
}
