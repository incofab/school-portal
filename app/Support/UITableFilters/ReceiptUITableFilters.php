<?php

namespace App\Support\UITableFilters;

use App\Enums\TermType;
use Illuminate\Validation\Rules\Enum;

class ReceiptUITableFilters extends BaseUITableFilter
{
  protected array $sortableColumns = [
    'createdAt' => 'created_at'
  ];

  protected function extraValidationRules(): array
  {
    return [
      'user' => ['sometimes', 'integer'],
      'paymentableType' => ['sometimes', 'string'],
      'paymentableId' => ['sometimes', 'integer'],
      'academicSession' => ['sometimes', 'integer'],
      'term' => ['sometimes', new Enum(TermType::class)]
    ];
  }

  protected function generalSearch(string $search)
  {
    return $this;
  }

  public function joinFee(): static
  {
    $this->callOnce(
      'joinFee',
      fn() => $this->baseQuery->join('fees', 'fees.id', 'receipts.fee_id')
    );
    return $this;
  }

  protected function directQuery()
  {
    $this->joinFee();

    $this->baseQuery
      ->when(
        $this->requestGet('institution_id'),
        fn($q, $value) => $q->where('receipts.institution_id', $value)
      )
      ->when(
        $this->requestGet('user'),
        fn($q, $value) => $q->where('receipts.user_id', $value)
      )
      ->when(
        $this->requestGet('paymentableType') &&
          $this->requestGet('paymentableId'),
        fn($q, $value) => $q
          ->where('fees.paymentable_type', $this->requestGet('paymentableType'))
          ->where('fees.paymentable_id', $this->requestGet('paymentableId'))
      )
      ->when(
        $this->requestGet('academicSession'),
        fn($q, $value) => $q->where('receipts.academic_session_id', $value)
      )
      ->when(
        $this->requestGet('term'),
        fn($q, $value) => $q->where('receipts.term', $value)
      );

    return $this;
  }
}
