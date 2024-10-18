<?php
namespace App\Actions\Payments;

use App\Models\Institution;
use App\Models\ReceiptType;
use Illuminate\Database\Eloquent\Builder;

class PaymentStat
{
  /**
   * @param Institution $institution
   * @param ReceiptType $receiptType
   * @param ?AcademicSession $academicSession
   * @param ?string|null $term
   */
  function __construct(
    private Institution $institution,
    private ReceiptType $receiptType,
    private $academicSession,
    private $term
  ) {
  }

  public function upload(Builder $query)
  {
    $numOfPayments = (clone $query)->count('id');
    $totalAmountPaid = (clone $query)->count('amount');
  }
}
