<?php

namespace App\Support\UITableFilters;

use App\Enums\ReceiptStatus;
use App\Enums\TermType;
use Illuminate\Validation\Rules\Enum;

class ReceiptUITableFilters extends BaseUITableFilter
{
  protected array $sortableColumns = [
    'student' => 'users.last_name',
    'feeTitle' => 'fees.title',
    'amount' => 'receipts.amount',
    'amountRemaining' => 'receipts.amount_remaining',
    'status' => 'receipts.status',
    'term' => 'receipts.term',
    'createdAt' => 'receipts.created_at'
  ];

  protected function extraValidationRules(): array
  {
    return [
      'user' => ['sometimes', 'integer'],
      'paymentableType' => ['sometimes', 'string'],
      'paymentableId' => ['sometimes', 'integer'],
      'academicSession' => ['sometimes', 'integer'],
      'term' => ['sometimes', new Enum(TermType::class)],
      'classification' => ['sometimes', 'integer'],
      'fee' => ['sometimes', 'integer'],
      'status' => ['sometimes', new Enum(ReceiptStatus::class)]
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

  public function joinUser(): static
  {
    $this->callOnce(
      'joinUser',
      fn() => $this->baseQuery->join('users', 'users.id', 'receipts.user_id')
    );
    return $this;
  }

  protected function joinStudents(): static
  {
    return $this->callOnce(
      'joinStudents',
      fn() => $this->baseQuery->join(
        'students',
        'receipts.user_id',
        'students.user_id'
      )
    );
  }

  protected function directQuery()
  {
    $this->joinFee();

    $this->when(
      $this->requestGet('classification'),
      fn(self $that, $value) => $that
        ->joinStudents()
        ->baseQuery->where('students.classification_id', $value)
    )
      ->baseQuery->when(
        $this->requestGet('institution_id'),
        fn($q, $value) => $q->where('receipts.institution_id', $value)
      )
      ->when(
        $this->requestGet('user'),
        fn($q, $value) => $q->where('receipts.user_id', $value)
      )
      ->when(
        $this->requestGet('fee'),
        fn($q, $value) => $q->where('receipts.fee_id', $value)
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
      )
      ->when(
        $this->requestGet('status'),
        fn($q, $value) => $q->where('receipts.status', $value)
      );

    if ($this->requestGet('sortKey') === 'student') {
      $this->joinUser();
    }

    return $this;
  }
}
