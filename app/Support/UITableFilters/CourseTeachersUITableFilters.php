<?php

namespace App\Support\UITableFilters;

class CourseTeachersUITableFilters extends BaseUITableFilter
{
  protected array $sortableColumns = [
    'createdAt' => 'created_at'
  ];

  protected function extraValidationRules(): array
  {
    return [
      'user' => ['sometimes', 'integer'],
      'course' => ['sometimes', 'integer'],
      'classification' => ['sometimes', 'integer']
    ];
  }

  protected function generalSearch(string $search)
  {
    $this->joinUser()
      ->joinClassification()
      ->baseQuery->where(
        fn($q) => $q
          ->where('users.last_name', 'like', "%$search%")
          ->orWhere('users.first_name', 'like', "%$search%")
          ->orWhere('users.email', $search)
          ->orWhere('classifications.title', 'like', "%$search%")
      );
  }

  private function joinUser(): static
  {
    $this->callOnce(
      'joinUser',
      fn() => $this->baseQuery->join(
        'users',
        'users.id',
        'course_teachers.user_id'
      )
    );
    return $this;
  }

  private function joinClassification(): static
  {
    $this->callOnce(
      'joinClassification',
      fn() => $this->baseQuery->join(
        'classifications',
        'classifications.id',
        'course_teachers.classification_id'
      )
    );
    return $this;
  }

  protected function directQuery()
  {
    $this->when(
      $this->requestGet('institution_id') ||
        $this->requestGet('classification'),
      fn(self $that) => $that->joinClassification()
    )
      // ->when($this->requestGet('course'), fn(self $that) => $that->joinCourse())
      // ->when($this->requestGet('user'), fn(self $that) => $that->joinUser())
      ->baseQuery->when(
        $this->requestGet('institution_id'),
        fn($q, $value) => $q->where('classifications.institution_id', $value)
      )
      ->when(
        $this->requestGet('classification'),
        fn($q, $value) => $q->where('course_teachers.classification_id', $value)
      )
      ->when(
        $this->requestGet('course'),
        fn($q, $value) => $q->where('course_teachers.course_id', $value)
      )
      ->when(
        $this->requestGet('user'),
        fn($q, $value) => $q->where('course_teachers.user_id', $value)
      );

    return $this;
  }
}
