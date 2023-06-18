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
      'term' => ['sometimes', new Enum(TermType::class)]
    ];
  }

  protected function generalSearch(string $search)
  {
  }

  protected function directQuery()
  {
    $this->baseQuery
      ->when(
        $this->requestGet('institution_id'),
        fn($q, $value) => $q->where('institution_id', $value)
      )
      ->when(
        $this->requestGet('fee'),
        fn($q, $value) => $q->where('fee_id', $value)
      )
      ->when(
        $this->requestGet('user'),
        fn($q, $value) => $q->where('user_id', $value)
      )
      ->when(
        $this->requestGet('academicSession'),
        fn($q, $value) => $q->where('academic_session_id', $value)
      )
      ->when(
        $this->requestGet('term'),
        fn($q, $value) => $q->where('term', $value)
      );

    return $this;
  }
}
