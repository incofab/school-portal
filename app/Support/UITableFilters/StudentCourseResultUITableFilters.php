<?php

namespace App\Support\UITableFilters;

use App\Enums\TermType;
use Illuminate\Validation\Rules\Enum;

class StudentCourseResultUITableFilters extends BaseUITableFilter
{
  protected array $sortableColumns = [
    'createdAt' => 'created_at'
  ];

  protected function extraValidationRules(): array
  {
    return [
      'student' => ['sometimes', 'integer'],
      'teacher' => ['sometimes', 'integer'],
      'course' => ['sometimes', 'integer'],
      'classification' => ['sometimes', 'integer'],
      'academicSession' => ['sometimes', 'integer'],
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
        'student_course_results.course_id'
      )
    );
    return $this;
  }

  protected function directQuery()
  {
    $this->when(
      $this->requestGet('institution_id'),
      fn(self $that) => $that->joinCourse()
    )
      ->baseQuery->when(
        $this->requestGet('institution_id'),
        fn($q, $value) => $q->where('courses.institution_id', $value)
      )
      ->when(
        $this->requestGet('classification'),
        fn($q, $value) => $q->where(
          'student_course_results.classification_id',
          $value
        )
      )
      ->when(
        $this->requestGet('course'),
        fn($q, $value) => $q->where('student_course_results.course_id', $value)
      )
      ->when(
        $this->requestGet('student'),
        fn($q, $value) => $q->where('student_course_results.student_id', $value)
      )
      ->when(
        $this->requestGet('teacher'),
        fn($q, $value) => $q->where(
          'student_course_results.teacher_user_id',
          $value
        )
      )
      ->when(
        $this->getAcademicSession(),
        fn($q, $value) => $q->where(
          'student_course_results.academic_session_id',
          $value
        )
      )
      ->when(
        $this->getTerm(),
        fn($q, $value) => $q->where('student_course_results.term', $value)
      );

    return $this;
  }
}
