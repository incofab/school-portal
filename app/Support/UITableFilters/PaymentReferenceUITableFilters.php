<?php

namespace App\Support\UITableFilters;

use App\Enums\Payments\PaymentMerchantType;
use App\Enums\Payments\PaymentPurpose;
use App\Enums\Payments\PaymentStatus;
use Illuminate\Validation\Rules\Enum;

class PaymentReferenceUITableFilters extends BaseUITableFilter
{
  protected array $sortableColumns = [
    'createdAt' => 'payment_references.created_at',
    'amount' => 'payment_references.amount',
    'status' => 'payment_references.status',
    'provider' => 'payment_references.merchant',
    'purpose' => 'payment_references.purpose'
  ];

  protected function extraValidationRules(): array
  {
    return [
      'status' => ['nullable', new Enum(PaymentStatus::class)],
      'merchant' => ['nullable', new Enum(PaymentMerchantType::class)],
      'purpose' => ['nullable', new Enum(PaymentPurpose::class)],
      'user' => ['sometimes', 'integer'],
      'payableType' => ['sometimes', 'string'],
      'payableId' => ['sometimes', 'integer'],
      'paymentableType' => ['sometimes', 'string'],
      'paymentableId' => ['sometimes', 'integer'],
      'reference' => ['nullable', 'string', 'max:100'],
      'date_from' => ['nullable', 'date'],
      'date_to' => ['nullable', 'date', 'after_or_equal:date_from']
    ];
  }

  protected function generalSearch(string $search)
  {
    $this->baseQuery->where(
      fn($query) => $query
        ->where('payment_references.reference', 'like', "%{$search}%")
        ->orWhere('payment_references.purpose', 'like', "%{$search}%")
        ->orWhere('payment_references.merchant', 'like', "%{$search}%")
    );

    return $this;
  }

  protected function directQuery()
  {
    $this->baseQuery
      ->when(
        $this->requestGet('institution_id'),
        fn($query, $value) => $query->where(
          'payment_references.institution_id',
          $value
        )
      )
      ->when(
        $this->requestGet('user'),
        fn($query, $value) => $query->where(
          'payment_references.user_id',
          $value
        )
      )
      ->when(
        $this->requestGet('status'),
        fn($query, $value) => $query->where(
          'payment_references.status',
          $value
        )
      )
      ->when(
        $this->requestGet('merchant'),
        fn($query, $value) => $query->where(
          'payment_references.merchant',
          $value
        )
      )
      ->when(
        $this->requestGet('purpose'),
        fn($query, $value) => $query->where(
          'payment_references.purpose',
          $value
        )
      )
      ->when(
        $this->requestGet('reference'),
        fn($query, $value) => $query->where(
          'payment_references.reference',
          'like',
          "%{$value}%"
        )
      )
      ->when(
        $this->requestGet('payableType') && $this->requestGet('payableId'),
        fn($query) => $query
          ->where(
            'payment_references.payable_type',
            $this->requestGet('payableType')
          )
          ->where(
            'payment_references.payable_id',
            $this->requestGet('payableId')
          )
      )
      ->when(
        $this->requestGet('paymentableType') &&
          $this->requestGet('paymentableId'),
        fn($query) => $query
          ->where(
            'payment_references.paymentable_type',
            $this->requestGet('paymentableType')
          )
          ->where(
            'payment_references.paymentable_id',
            $this->requestGet('paymentableId')
          )
      )
      ->when(
        $this->requestGet('date_from'),
        fn($query, $value) => $query->whereDate(
          'payment_references.created_at',
          '>=',
          $value
        )
      )
      ->when(
        $this->requestGet('date_to'),
        fn($query, $value) => $query->whereDate(
          'payment_references.created_at',
          '<=',
          $value
        )
      );

    return $this;
  }
}
