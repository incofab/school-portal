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

  private function joinCourse(): static
  {
    $this->callOnce(
      'joinCourse',
      fn() => $this->baseQuery->join(
        'courses',
        'courses.id',
        'course_teachers.course_id'
      )
    );
    return $this;
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

  protected function directQuery()
  {
    $this->when(
      $this->requestGet('institution_id') ||
        $this->requestGet('classification'),
      fn(self $that) => $that->joinClassification()
    )
      ->when($this->requestGet('course'), fn(self $that) => $that->joinCourse())
      ->when($this->requestGet('user'), fn(self $that) => $that->joinUser())
      ->baseQuery->when(
        $this->requestGet('institution_id'),
        fn($q, $value) => $q->where('classifications.institution_id', $value)
      )
      ->when(
        $this->requestGet('classification'),
        fn($q, $value) => $q->where('classifications.id', $value)
      )
      ->when(
        $this->requestGet('course'),
        fn($q, $value) => $q->where('courses.id', $value)
      )
      ->when(
        $this->requestGet('user'),
        fn($q, $value) => $q->where('users.id', $value)
      );

    return $this;
  }
}
