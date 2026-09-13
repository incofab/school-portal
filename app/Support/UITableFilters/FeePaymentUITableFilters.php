<?php

namespace App\Support\UITableFilters;

use App\Enums\Payments\PaymentMethod;
use App\Enums\ReceiptStatus;
use App\Enums\TermType;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class FeePaymentUITableFilters extends BaseUITableFilter
{
  protected array $sortableColumns = [
    'feeTitle' => 'fees.title',
    'student' => 'users.last_name',
    'amount' => 'fee_payments.amount',
    'method' => 'fee_payments.method',
    'createdAt' => 'fee_payments.created_at'
  ];

  protected function extraValidationRules(): array
  {
    return [
      'fee' => ['sometimes', 'integer'],
      'user' => ['sometimes', 'integer'],
      'classification' => ['sometimes', 'integer'],
      'academicSession' => ['sometimes', 'integer'],
      'receipt' => ['nullable', 'integer'],
      'term' => ['sometimes', new Enum(TermType::class)],
      'status' => ['sometimes', new Enum(ReceiptStatus::class)],
      'method' => [
        'sometimes',
        Rule::in(
          array_map(fn($method) => $method->value, PaymentMethod::cases())
        )
      ],
      'reference' => ['sometimes', 'string']
    ];
  }

  protected function generalSearch(string $search)
  {
    $this->joinFee()->joinUser();
    $this->baseQuery->where(
      fn($query) => $query
        ->where('fees.title', 'like', "%$search%")
        ->orWhere('users.last_name', 'like', "%$search%")
        ->orWhere('users.first_name', 'like', "%$search%")
        ->orWhere('fee_payments.reference', 'like', "%$search%")
        ->orWhere('fee_payments.method', 'like', "%$search%")
    );

    return $this;
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

  protected function joinStudents(): static
  {
    return $this->joinReceipt()->callOnce(
      'joinStudents',
      fn() => $this->baseQuery->join(
        'students',
        'receipts.user_id',
        'students.user_id'
      )
    );
  }

  public function joinFee(): static
  {
    $this->callOnce(
      'joinFee',
      fn() => $this->baseQuery->join('fees', 'fees.id', 'fee_payments.fee_id')
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

  protected function directQuery()
  {
    $this->joinReceipt();

    $this->when(
      $this->requestGet('classification'),
      fn(self $that, $value) => $that
        ->joinStudents()
        ->baseQuery->where('students.classification_id', $value)
    )
      ->baseQuery->when(
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
      )
      ->when(
        $this->requestGet('status'),
        fn($q, $value) => $q->where('receipts.status', $value)
      )
      ->when(
        $this->requestGet('method'),
        fn($q, $value) => $q->where('fee_payments.method', $value)
      )
      ->when(
        $this->requestGet('reference'),
        fn($q, $value) => $q->where(
          'fee_payments.reference',
          'like',
          "%$value%"
        )
      );

    if ($this->requestGet('sortKey') === 'feeTitle') {
      $this->joinFee();
    }

    if ($this->requestGet('sortKey') === 'student') {
      $this->joinUser();
    }

    return $this;
  }
}
