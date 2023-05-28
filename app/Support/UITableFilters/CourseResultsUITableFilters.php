<?php

namespace App\Support\UITableFilters;

use App\Enums\TermType;
use Illuminate\Validation\Rules\Enum;

class CourseResultsUITableFilters extends BaseUITableFilter
{
  protected array $sortableColumns = [
    'createdAt' => 'created_at'
  ];

  protected function extraValidationRules(): array
  {
    return [
      'student' => ['sometimes', 'integer'],
      'course' => ['sometimes', 'integer'],
      'classification' => ['sometimes', 'integer'],
      'student' => ['sometimes', 'integer'],
      'teacher' => ['sometimes', 'integer'],
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
        fn($q, $value) => $q->where('course-results.institution_id', $value)
      )
      ->when(
        $this->requestGet('classification'),
        fn($q, $value) => $q->where('course-results.classification_id', $value)
      )
      ->when(
        $this->requestGet('student'),
        fn($q, $value) => $q->where('course-results.student_id', $value)
      )
      ->when(
        $this->requestGet('teacher'),
        fn($q, $value) => $q->where('course-results.teacher_user_id', $value)
      )
      ->when(
        $this->requestGet('course'),
        fn($q, $value) => $q->where('course-results.course_id', $value)
      )
      ->when(
        $this->requestGet('academicSession'),
        fn($q, $value) => $q->where(
          'course-results.academic_session_id',
          $value
        )
      )
      ->when(
        $this->requestGet('term'),
        fn($q, $value) => $q->where('course-results.term', $value)
      );

    return $this;
  }
}
