<?php

namespace App\Support\UITableFilters;

use App\Enums\TermType;
use App\Models\InstitutionUser;
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
    $this->joinCourse()
      ->joinClassification()
      ->baseQuery->where(function ($q) use ($search) {
        $q->where('courses.title', 'like', "%{$search}%")->orWhere(
          'classifications.name',
          'like',
          "%{$search}%"
        );
      });
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

  private function joinClassification(): static
  {
    $this->callOnce(
      'joinClassification',
      fn() => $this->baseQuery->join(
        'classifications',
        'classifications.id',
        'course_result_info.classification_id'
      )
    );
    return $this;
  }

  private function joinCourseTeacher(): static
  {
    $this->callOnce(
      'joinCourseTeacher',
      fn() => $this->baseQuery->join(
        'course_teachers',
        'course_teachers.classification_id',
        'course_result_info.classification_id'
      )
    );
    return $this;
  }

  function forTeacher(?InstitutionUser $institutionUser = null): static
  {
    if (!$institutionUser || !$institutionUser->isTeacher()) {
      return $this;
    }

    $this->joinClassification()
      ->joinCourseTeacher()
      ->baseQuery->where(
        fn($q) => $q
          ->where('classifications.form_teacher_id', $institutionUser->user_id)
          ->orWhere('course_teachers.user_id', $institutionUser->user_id)
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
