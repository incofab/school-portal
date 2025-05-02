<?php

namespace App\Support\UITableFilters;

use App\Enums\TermType;
use Illuminate\Validation\Rules\Enum;

class FeeUITableFilters extends BaseUITableFilter
{
  protected array $sortableColumns = [
    'createdAt' => 'created_at'
  ];

  protected function extraValidationRules(): array
  {
    return [
      'academicSession' => ['sometimes', 'integer'],
      'term' => ['sometimes', new Enum(TermType::class)]
    ];
  }

  protected function generalSearch(string $search)
  {
    return $this;
  }

  protected function directQuery()
  {
    $this->baseQuery
      ->when(
        $this->requestGet('institution_id'),
        fn($q, $value) => $q->where('fees.institution_id', $value)
      )
      ->when(
        $this->getAcademicSession(),
        fn($q, $value) => $q->where(
          fn($qq) => $qq
            ->whereNull('fees.academic_session_id')
            ->orWhere('fees.academic_session_id', $value)
        )
      )
      ->when(
        $this->getTerm(),
        fn($q, $value) => $q->where(
          fn($qq) => $qq->whereNull('fees.term')->orWhere('fees.term', $value)
        )
      );

    return $this;
  }
}
