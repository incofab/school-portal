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
      'receiptType' => ['sometimes', 'integer'],
      'classification' => ['sometimes', 'integer'],
      'classificationGroup' => ['sometimes', 'integer'],
      'academicSession' => ['sometimes', 'integer'],
      'approvedBy' => ['sometimes', 'integer'],
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
        $this->requestGet('receiptType'),
        fn($q, $value) => $q->where('receipt_type_id', $value)
      )
      ->when(
        $this->requestGet('user'),
        fn($q, $value) => $q->where('user_id', $value)
      )
      ->when(
        $this->requestGet('classification'),
        fn($q, $value) => $q->where('classification_id', $value)
      )
      ->when(
        $this->requestGet('classificationGroup'),
        fn($q, $value) => $q->where('classification_group_id', $value)
      )
      ->when(
        $this->requestGet('approvedBy'),
        fn($q, $value) => $q->where('approved_by_user_id', $value)
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
