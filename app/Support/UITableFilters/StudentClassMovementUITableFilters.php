<?php

namespace App\Support\UITableFilters;

class StudentClassMovementUITableFilters extends BaseUITableFilter
{
  protected array $sortableColumns = [
    'createdAt' => 'created_at'
  ];

  protected function extraValidationRules(): array
  {
    return [
      'reason' => ['sometimes', 'string'],
      'batchNo' => ['sometimes', 'string'],
      'destinationClass' => ['sometimes', 'integer'],
      'sourceClass' => ['sometimes', 'integer'],
      'student' => ['sometimes', 'integer'],
      'user' => ['sometimes', 'integer'],
      'revertReference' => ['sometimes', 'string']
    ];
  }

  protected function generalSearch(string $search)
  {
    $this->baseQuery->where(
      fn($q) => $q
        ->where('reason', 'LIKE', "%$search%")
        ->orWhere('note', 'LIKE', "%$search%")
        ->orWhere('batch_no', '=', $search)
    );
  }

  protected function directQuery()
  {
    $this->baseQuery
      ->when(
        $this->requestGet('institution_id'),
        fn($q, $value) => $q->where('institution_id', $value)
      )
      ->when(
        $this->requestGet('batchNo'),
        fn($q, $value) => $q->where('batch_no', $value)
      )
      ->when(
        $this->requestGet('destinationClass'),
        fn($q, $value) => $q->where('destination_classification_id', $value)
      )
      ->when(
        $this->requestGet('sourceClass'),
        fn($q, $value) => $q->where('source_classification_id', $value)
      )
      ->when(
        $this->requestGet('student'),
        fn($q, $value) => $q->where('student_id', $value)
      )
      ->when(
        $this->requestGet('user'),
        fn($q, $value) => $q->where('user_id', $value)
      )
      ->when(
        $this->requestGet('revertReference'),
        fn($q, $value) => $q->where('revert_reference_id', $value)
      )
      ->when(
        $this->requestGet('reason'),
        fn($q, $value) => $q->where('reason', $value)
      );

    return $this;
  }
}
