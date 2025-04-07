<?php

namespace App\Support\Fundings;

use App\Enums\WalletType;
use App\Models\Funding;
use App\Models\InstitutionGroup;
use App\Models\PaymentReference;
use App\Models\User;
use App\Support\Res;
use Illuminate\Database\Eloquent\Model;

class FundingHandler
{
  private $principalAmount;
  private $debtReference;
  private $creditReference;
  private ?string $remark;

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
    $this->remark = $data['remark'] ?? '';
    $reference = $data['reference'];
    $this->debtReference = Funding::debtReference($reference);
    $this->creditReference = Funding::creditReference($reference);
  }

  static function makeFromPaymentRef(
    PaymentReference $paymentRef,
    $remark = ''
  ): static {
    return new self(
      $paymentRef->institution->institutionGroup,
      $paymentRef->user,
      [
        'reference' => $paymentRef->reference,
        'amount' => $paymentRef->amount,
        'remark' => $remark
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

  function requestDebt(?Model $fundable = null): Res
  {
    return $this->giveLoan($this->principalAmount, $fundable);
  }

  function processWalletPayment(?Model $fundable)
  {
    $surplus = $this->payDebt($this->principalAmount, $fundable);
    if ($surplus > 0) {
      RecordFunding::make(
        $this->institutionGroup,
        $this->user
      )->recordCreditTopup(
        $surplus,
        $this->creditReference,
        $fundable,
        $this->remark
      );
    }
    return failRes('Not enough amount remaining after debt');
  }

  function giveLoan($amount, ?Model $fundable = null)
  {
    RecordFunding::make($this->institutionGroup, $this->user)->recordDebtTopup(
      $amount,
      $this->debtReference,
      $fundable,
      $this->remark
    );

    RecordFunding::make(
      $this->institutionGroup,
      $this->user
    )->recordCreditTopup(
      $amount,
      $this->creditReference,
      $fundable,
      $this->remark
    );

    return successRes('Loan given');
  }

  function payDebt($amount, ?Model $fundable)
  {
    if (!$this->institutionGroup->isOwing()) {
      return $amount;
    }
    $prevDebtBal = $this->institutionGroup->debt_wallet;
    $debtToPay = $amount > $prevDebtBal ? $prevDebtBal : $amount;

    RecordFunding::make(
      $this->institutionGroup,
      $this->user
    )->recordDebtReduction(
      $debtToPay,
      $this->debtReference,
      $fundable,
      $this->remark ?? 'Pay debt'
    );
    return $amount - $prevDebtBal;
  }
}
