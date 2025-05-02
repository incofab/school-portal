<?php

namespace App\Support\UITableFilters;

use App\Enums\TermType;
use Illuminate\Validation\Rules\Enum;

class FeePaymentUITableFilters extends BaseUITableFilter
{
  protected array $sortableColumns = [
    'createdAt' => 'created_at'
  ];

  protected function extraValidationRules(): array
  {
    return [
      'fee' => ['sometimes', 'integer'],
      'user' => ['sometimes', 'integer'],
      'academicSession' => ['sometimes', 'integer'],
      'receipt' => ['nullable', 'integer'],
      'term' => ['sometimes', new Enum(TermType::class)]
    ];
  }

  protected function generalSearch(string $search)
  {
  }

  public function joinReceipt(): static
  {
    $this->callOnce(
      'joinReceipt',
      fn() => $this->baseQuery->join(
        'receipts',
        'receipts.id',
        'fee_payments.receipt_id'
      )
    );
    return $this;
  }

  protected function directQuery()
  {
    $this->joinReceipt();

    $this->baseQuery
      ->when(
        $this->requestGet('institution_id'),
        fn($q, $value) => $q->where('fee_payments.institution_id', $value)
      )
      ->when(
        $this->requestGet('fee'),
        fn($q, $value) => $q->where('fee_payments.fee_id', $value)
      )
      ->when(
        $this->requestGet('user'),
        fn($q, $value) => $q->where('receipts.user_id', $value)
      )
      ->when(
        $this->requestGet('academicSession'),
        fn($q, $value) => $q->where('receipts.academic_session_id', $value)
      )
      ->when(
        $this->requestGet('receipt'),
        fn($q, $value) => $q->where('receipt_id', $value)
      )
      ->when(
        $this->requestGet('term'),
        fn($q, $value) => $q->where('receipts.term', $value)
      );

    return $this;
  }
}
