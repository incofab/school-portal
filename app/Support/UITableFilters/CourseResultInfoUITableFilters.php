<?php

namespace App\Support\UITableFilters;

use App\Enums\TermType;
use Illuminate\Validation\Rules\Enum;

class CourseResultInfoUITableFilters extends BaseUITableFilter
{
  protected array $sortableColumns = [
    'createdAt' => 'created_at'
  ];

  protected function extraValidationRules(): array
  {
    return [
      'course' => ['sometimes', 'integer'],
      'classification' => ['sometimes', 'integer'],
      'academicSession' => ['sometimes', 'integer'],
      'forMidTerm' => ['sometimes', 'boolean'],
      'term' => ['sometimes', new Enum(TermType::class)]
    ];
  }

  protected function generalSearch(string $search)
  {
  }

  private function joinCourse(): static
  {
    $this->callOnce(
      'joinCourse',
      fn() => $this->baseQuery->join(
        'courses',
        'courses.id',
        'course_result_info.course_id'
      )
    );
    return $this;
  }

  protected function directQuery()
  {
    $this->baseQuery
      ->when(
        $this->requestGet('institution_id'),
        fn($q, $value) => $q->where('course_result_info.institution_id', $value)
      )
      ->when(
        $this->requestGet('classification'),
        fn($q, $value) => $q->where(
          'course_result_info.classification_id',
          $value
        )
      )
      ->when(
        $this->requestGet('course'),
        fn($q, $value) => $q->where('course_result_info.course_id', $value)
      )
      ->when(
        $this->getAcademicSession(),
        fn($q, $value) => $q->where(
          'course_result_info.academic_session_id',
          $value
        )
      )
      ->when(
        $this->getTerm(),
        fn($q, $value) => $q->where('course_result_info.term', $value)
      )
      ->when(
        $this->requestGet('forMidTerm') !== null,
        fn($q, $value) => $q->where(
          'course_result_info.for_mid_term',
          $this->requestGet('forMidTerm')
        )
      );

    return $this;
  }
}
