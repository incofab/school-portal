<?php

namespace App\Support\UITableFilters;

use App\Models\Student;
use App\Support\MorphMap;

class ExamUITableFilters extends BaseUITableFilter
{
  protected array $sortableColumns = [
    'createdAt' => 'created_at'
  ];

  protected function extraValidationRules(): array
  {
    return [
      'classification' => ['sometimes', 'integer']
    ];
  }

  protected function generalSearch(string $search)
  {
    $this->baseQuery->where('exam_no', 'LIKE', "%$search%");
    return $this;
  }

  public function joinStudent(): static
  {
    $this->callOnce(
      'joinStudent',
      fn() => $this->baseQuery->join(
        'students',
        fn($q) => $q
          ->on('students.id', 'exams.examable_id')
          ->where('exams.examable_type', MorphMap::key(Student::class))
      )
    );
    return $this;
  }

  protected function directQuery()
  {
    $this->when(
      $this->requestGet('classification'),
      fn($that, $value) => $that
        ->joinStudent()
        ->baseQuery->where('students.classification_id', $value)
    );

    return $this;
  }
}
