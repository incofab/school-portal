<?php

namespace App\Support\UITableFilters;

class SessionResultUITableFilters extends BaseUITableFilter
{
  protected array $sortableColumns = [
    'createdAt' => 'created_at'
  ];

  protected function extraValidationRules(): array
  {
    return [
      'student' => ['sometimes', 'integer'],
      'classification' => ['sometimes', 'integer'],
      'academicSession' => ['sometimes', 'integer']
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
        fn($q, $value) => $q->where('session_results.institution_id', $value)
      )
      ->when(
        $this->requestGet('student'),
        fn($q, $value) => $q->where('session_results.student_id', $value)
      )
      ->when(
        $this->requestGet('classification'),
        fn($q, $value) => $q->where('session_results.classification_id', $value)
      )
      ->when(
        $this->getAcademicSession(),
        fn($q, $value) => $q->where(
          'session_results.academic_session_id',
          $value
        )
      )
      ->when(
        $this->requestGet('forMidTerm') !== null,
        fn($q, $value) => $q->where(
          'session_results.for_mid_term',
          $this->requestGet('forMidTerm')
        )
      );
    return $this;
  }
}
