<?php

namespace App\Support\UITableFilters;

class TopicUITableFilters extends BaseUITableFilter
{
  protected array $sortableColumns = [
    'title' => 'title',
    'createdAt' => 'created_at'
  ];

  protected function extraValidationRules(): array
  {
    return [
      'classificationGroup' => ['sometimes', 'integer'],
      'course' => ['sometimes', 'integer']
    ];
  }

  protected function generalSearch(string $search)
  {
    $this->baseQuery->where(
      fn($q) => $q->where('topics.title', 'like', "%$search%")
    );
  }

  protected function directQuery()
  {
    $this->baseQuery
      ->when(
        $this->requestGet('classificationGroup'),
        fn($q, $value) => $q->where('topics.classification_group_id', $value)
      )
      ->when(
        $this->requestGet('course'),
        fn($q, $value) => $q->where('topics.course_id', $value)
      );

    return $this;
  }
}
