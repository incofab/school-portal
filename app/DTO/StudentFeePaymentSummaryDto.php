<?php
namespace App\DTO;

use App\Models\Student;
use ArrayAccess;
use JsonSerializable;

class StudentFeePaymentSummaryDto implements JsonSerializable
{
  /**
   *  @param FeeSummaryDto[] $paymentSummaries
   * @param float $totalAmountToPay
   */
  function __construct(
    private Student $student,
    private array $paymentSummaries = [],
    private float $totalAmountToPay = 0
  ) {
  }

  function getStudent()
  {
    return $this->student;
  }

  function updateTotalAmountToPay($amountToPay)
  {
    $this->totalAmountToPay += $amountToPay;
  }

  function addPaymentSummary(FeeSummaryDto $paymentSummary)
  {
    $this->paymentSummaries[] = $paymentSummary;
  }

  function getPaymentSummaries()
  {
    return $this->paymentSummaries;
  }

  function getTotalAmountToPay()
  {
    return $this->totalAmountToPay;
  }

  public function jsonSerialize(): mixed
  {
    return [
      'student' => $this->student,
      'total_amount_to_pay' => $this->totalAmountToPay,
      'payment_summaries' => $this->paymentSummaries
    ];
  }
}

/**
 * @property float $amount_remaining
 * @property float $amount_paid
 * @property string $title
 * @property bool $is_part_payment
 * @property int $fee_id
 */
class FeeSummaryDto implements ArrayAccess, JsonSerializable
{
  function __construct(protected array $data)
  {
  }
  static function new(
    float $amount_remaining,
    float $amount_paid,
    string $title,
    bool $is_part_payment,
    int $fee_id
  ): static {
    return new FeeSummaryDto([
      'amount_remaining' => $amount_remaining,
      'amount_paid' => $amount_paid,
      'title' => $title,
      'is_part_payment' => $is_part_payment,
      'fee_id' => $fee_id
    ]);
  }

  function __get($name): mixed
  {
    return $this->offsetGet($name);
  }

  function __set($name, $value): void
  {
    $this->offsetSet($name, $value);
  }

  function offsetExists(mixed $key): bool
  {
    return isset($this->data[$key]);
  }

  function offsetSet(mixed $key, mixed $value): void
  {
    if (is_null($key)) {
      $this->data[] = $value;
    } else {
      $this->data[$key] = $value;
    }
  }

  function offsetGet(mixed $key): mixed
  {
    return $this->data[$key] ?? null;
  }

  public function offsetUnset(mixed $key): void
  {
    unset($this->data[$key]);
  }

  public function jsonSerialize(): mixed
  {
    return [
      'amount_remaining' => $this->amount_remaining,
      'amount_paid' => $this->amount_paid,
      'title' => $this->title,
      'is_part_payment' => $this->is_part_payment,
      'fee_id' => $this->fee_id
    ];
  }
}
