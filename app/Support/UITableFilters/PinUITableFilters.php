<?php

namespace App\Support\UITableFilters;

use App\Enums\TermType;
use Illuminate\Validation\Rules\Enum;

class PinUITableFilters extends BaseUITableFilter
{
  protected array $sortableColumns = [
    'createdAt' => 'created_at'
  ];

  protected function extraValidationRules(): array
  {
    return [
      'academicSession' => ['sometimes', 'integer'],
      'term' => ['sometimes', new Enum(TermType::class)],
      'classification' => ['sometimes', 'integer'],
      'academicSession' => ['sometimes', 'integer'],
      'pin_generator_id' => ['sometimes', 'integer']
    ];
  }

  protected function generalSearch(string $search)
  {
    $this->baseQuery->where('pin', 'LIKE', "%$search%");
    return $this;
  }

  public function joinStudent(): static
  {
    $this->callOnce(
      'joinStudent',
      fn() => $this->baseQuery
        ->join('students', 'students.id', 'pins.student_id')
        ->join('users', 'users.id', 'students.user_id')
    );
    return $this;
  }

  protected function directQuery()
  {
    $this->baseQuery
      ->when(
        $this->requestGet('institution_id'),
        fn($q, $value) => $q->where('institution_id', $value)
      )
      ->when(
        $this->requestGet('academicSession'),
        fn($q, $value) => $q->where('academic_session_id', $value)
      )
      ->when(
        $this->requestGet('pin_generator_id'),
        fn($q, $value) => $q->where('pin_generator_id', $value)
      )
      ->when(
        $this->requestGet('term'),
        fn($q, $value) => $q->where('term', $value)
      );

    $this->when(
      $this->requestGet('classification'),
      fn($that, $value) => $that
        ->joinStudent()
        ->baseQuery->where('students.classification_id', $value)
    );

    return $this;
  }
}
