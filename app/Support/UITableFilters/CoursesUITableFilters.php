<?php

namespace App\Support\UITableFilters;

class CoursesUITableFilters extends BaseUITableFilter
{
  protected array $sortableColumns = [
    'order' => 'courses.order',
    'title' => 'courses.title',
    'createdAt' => 'created_at'
  ];

  protected function extraValidationRules(): array
  {
    return [
      'title' => ['sometimes', 'string'],
      'code' => ['sometimes', 'string'],
      'order' => ['sometimes', 'integer']
    ];
  }

  protected function generalSearch(string $search)
  {
    $this->baseQuery->where(
      fn($q) => $q
        ->where('courses.title', 'LIKE', "%$search%")
        ->orWhere('courses.code', '=', $search)
    );
  }

  protected function directQuery()
  {
    $this->baseQuery
      ->when(
        $this->requestGet('institution_id'),
        fn($q, $value) => $q->where('courses.institution_id', $value)
      )
      ->when(
        $this->requestGet('title'),
        fn($q, $value) => $q->where('courses.title', 'LIKE', "%$value%")
      )
      ->when(
        $this->requestGet('code'),
        fn($q, $value) => $q->where('courses.code', $value)
      )
      ->when(
        $this->requestGet('order') !== null,
        fn($q) => $q->where('courses.order', $this->requestGet('order'))
      );

    return $this;
  }
}
