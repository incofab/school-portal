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
      'studentClass' => ['sometimes', 'integer'],
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

  /** Important for sorting list by student names */
  public function joinStudent(): static
  {
    $this->callOnce(
      'joinStudent',
      fn() => $this->baseQuery
        ->join('users', 'users.id', 'receipts.user_id')
        ->join('students', 'students.user_id', 'users.id')
    );
    return $this;
  }

  protected function directQuery()
  {
    if ($this->requestGet('studentClass')) {
      $this->joinStudent();
    }

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
        $this->requestGet('studentClass'),
        fn($q, $value) => $q->where('students.classification_id', $value)
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
