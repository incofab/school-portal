<?php

namespace App\Support\UITableFilters;

use App\Enums\TermType;
use Illuminate\Validation\Rules\Enum;

class ClassResultInfoUITableFilters extends BaseUITableFilter
{
  protected array $sortableColumns = [
    'createdAt' => 'created_at'
  ];

  protected function extraValidationRules(): array
  {
    return [
      'classification' => ['sometimes', 'integer'],
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
        fn($q, $value) => $q->where('class_result_info.institution_id', $value)
      )
      ->when(
        $this->requestGet('classification'),
        fn($q, $value) => $q->where(
          'class_result_info.classification_id',
          $value
        )
      )
      ->when(
        $this->requestGet('academicSession'),
        fn($q, $value) => $q->where(
          'class_result_info.academic_session_id',
          $value
        )
      )
      ->when(
        $this->requestGet('term'),
        fn($q, $value) => $q->where('class_result_info.term', $value)
      );

    return $this;
  }
}
