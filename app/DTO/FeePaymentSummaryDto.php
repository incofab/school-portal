<?php
namespace App\DTO;

class FeePaymentSummaryDto
{
  /**
   * @param array {
   *  amount_remaining: float,
   *  amount_paid: float,
   *  title: string,
   *  is_part_payment: boolean
   * }[] $feesToPay
   */
  function __construct(
    private array $feesToPay = [],
    private float $totalAmountToPay = 0
  ) {
  }

  function updateTotalAmountToPay($amountToPay)
  {
    $this->totalAmountToPay += $amountToPay;
  }

  function addFeesToPay(array $feesToPay)
  {
    $this->feesToPay[] = $feesToPay;
  }

  function getFeeToPay()
  {
    return $this->feesToPay;
  }

  function getTotalAmountToPay()
  {
    return $this->totalAmountToPay;
  }
}
