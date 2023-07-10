<?php

namespace App\Support\UITableFilters;

use App\Enums\TermType;
use Illuminate\Validation\Rules\Enum;

class AssessmentUITableFilters extends BaseUITableFilter
{
  protected array $sortableColumns = [
    'createdAt' => 'created_at'
  ];

  protected function extraValidationRules(): array
  {
    return [
      'forMidTerm' => ['sometimes', 'boolean'],
      'term' => ['sometimes', new Enum(TermType::class)]
    ];
  }

  protected function generalSearch(string $search)
  {
    $this->baseQuery->where(
      fn($q) => $q
        ->where('assessments.title', 'LIKE', "%$search%")
        ->orWhere('assessments.description', 'LIKE', "%$search%")
    );
  }

  protected function directQuery()
  {
    $this->baseQuery
      ->when(
        $this->requestGet('institution_id'),
        fn($q, $value) => $q->where('assessments.institution_id', $value)
      )
      ->when(
        $this->requestGet('term'),
        fn($q, $value) => $q->where('assessments.term', $value)
      )
      ->when(
        $this->requestGet('forMidTerm'),
        fn($q, $value) => $q->where('assessments.for_mid_term', $value)
      );

    return $this;
  }
}
