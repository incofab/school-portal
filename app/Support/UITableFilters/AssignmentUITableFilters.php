<?php

namespace App\Support\UITableFilters;

use App\Enums\AssignmentStatus;
use App\Enums\AttendanceType;
use App\Enums\InstitutionUserType;
use Illuminate\Validation\Rules\Enum;

class AssignmentUITableFilters extends BaseUITableFilter
{
  protected array $sortableColumns = [
    'expiry' => 'expires_at',
    'createdAt' => 'created_at'
  ];

  protected function extraValidationRules(): array
  {
    return [
      'course' => ['sometimes', 'string'],
      'status' => ['sometimes', new Enum(AssignmentStatus::class)],
      'classification' => ['sometimes', 'string'],
    ];
  }

  protected function generalSearch(string $search)
  {
    return $this;
  }

  protected function directQuery()
  {
    $this->baseQuery->when(
      $this->requestGet('classification'),
      fn($q, $value) => $q->where('assignment_classifications.classification_id', $value)
    )->when(
      $this->requestGet('course'),
      fn($q, $value) => $q->where('assignments.course_id', $value)
    )->when(
      $this->requestGet('status'),
      fn($q, $value) => $q->where('assignments.status', $value)
    );
    return $this;
  }
}