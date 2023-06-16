<?php

namespace App\Support\UITableFilters;

use App\Enums\TermType;
use Illuminate\Validation\Rules\Enum;

class PinUITableFilters extends BaseUITableFilter
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
    $this->baseQuery->where('pin', 'LIKE', "%$search%");
    return $this;
  }

  protected function directQuery()
  {
    $this->baseQuery
      ->when(
        $this->requestGet('institution_id'),
        fn($q, $value) => $q->where('institution_id', $value)
      )
      ->when(
        $this->requestGet('academicSession'),
        fn($q, $value) => $q->where('academic_session_id', $value)
      )
      ->when(
        $this->requestGet('pin_generator_id'),
        fn($q, $value) => $q->where('pin_generator_id', $value)
      )
      ->when(
        $this->requestGet('pin_print_id'),
        fn($q, $value) => $q->where('pin_print_id', $value)
      )
      ->when(
        $this->requestGet('term'),
        fn($q, $value) => $q->where('term', $value)
      );

    return $this;
  }
}
