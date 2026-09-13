<?php

namespace App\Support\UITableFilters;

use App\Enums\AttendanceType;
use App\Enums\InstitutionUserType;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class AttendanceUITableFilters extends BaseUITableFilter
{
  protected array $sortableColumns = [
    'firstName' => 'users.first_name',
    'lastName' => 'users.last_name',
    'createdAt' => 'attendances.created_at',
    'signedInAt' => 'attendances.signed_in_at',
    'signedOutAt' => 'attendances.signed_out_at'
  ];

  protected function extraValidationRules(): array
  {
    return [
      'first_name' => ['sometimes', 'string'],
      'last_name' => ['sometimes', 'string'],
      'name' => ['sometimes', 'string'],
      'email' => ['sometimes', 'string'],
      'role' => ['sometimes', new Enum(InstitutionUserType::class)],
      'type' => ['sometimes', new Enum(AttendanceType::class)],
      'classification' => ['sometimes', 'integer'],
      'lateness' => ['sometimes', Rule::in(['late', 'on_time'])],
      'checkInTime' => [
        'sometimes',
        'date_format:H:i',
        'required_if:lateness,late,on_time'
      ],
      'roles_not_in' => ['sometimes', 'array'],
      'roles_in' => ['sometimes', 'array']
    ];
  }

  protected function extraDateRangeColumns(): array
  {
    return [
      'signed_in_at' => 'attendances.signed_in_at'
    ];
  }

  protected function generalSearch(string $search)
  {
    $this->joinInstitutionUser(true)->baseQuery->where(
      fn($q2) => $q2
        ->where('users.last_name', 'like', "%$search%")
        ->orWhere('users.first_name', 'like', "%$search%")
    );
  }

  protected function joinInstitutionUser($joinUser = false): static
  {
    $this->callOnce(
      'joinInstitutionUser',
      fn() => $this->baseQuery->join(
        'institution_users',
        'attendances.institution_user_id',
        'institution_users.id'
      )
    );
    if ($joinUser) {
      $this->callOnce(
        'joinUser',
        fn() => $this->baseQuery->join(
          'users',
          'users.id',
          'institution_users.user_id'
        )
      );
    }
    return $this;
  }

  protected function joinStudents(): static
  {
    return $this->joinInstitutionUser()->callOnce(
      'joinStudents',
      fn() => $this->baseQuery->join(
        'students',
        'institution_users.user_id',
        'students.user_id'
      )
    );
  }

  protected function directQuery()
  {
    $this->when(
      $this->requestGet('type'),
      fn(self $that, $type) => $that->baseQuery->when(
        $type === AttendanceType::In->value,
        fn($q) => $q->whereNotNull('signed_in_at'),
        fn($q) => $q->whereNotNull('signed_out_at')
      )
    )
      ->when(
        $this->requestGet('role'),
        fn(self $that, $role) => $that
          ->joinInstitutionUser()
          ->baseQuery->where('institution_users.type', $role)
      )
      ->when(
        $this->requestGet('roles_in'),
        fn(self $that, $value) => $that
          ->joinInstitutionUser()
          ->baseQuery->whereIn('institution_users.type', $value)
      )
      ->when(
        $this->requestGet('roles_not_in'),
        fn(self $that, $value) => $that
          ->joinInstitutionUser()
          ->baseQuery->whereNotIn('institution_users.type', $value)
      )
      ->when(
        $this->requestGet('first_name'),
        fn(self $that, $value) => $that
          ->joinInstitutionUser(true)
          ->baseQuery->where('users.first_name', 'like', "%$value%")
      )
      ->when(
        $this->requestGet('last_name'),
        fn(self $that, $value) => $that
          ->joinInstitutionUser(true)
          ->baseQuery->where('users.last_name', 'like', "%$value%")
      )
      ->when(
        $this->requestGet('name'),
        fn(self $that, $value) => $that
          ->joinInstitutionUser(true)
          ->baseQuery->where(
            fn($q2) => $q2
              ->where('users.last_name', 'like', "%$value%")
              ->orWhere('users.first_name', 'like', "%$value%")
          )
      )
      ->when(
        $this->requestGet('classification'),
        fn(self $that, $value) => $that
          ->joinStudents()
          ->baseQuery->where('students.classification_id', $value)
      )
      ->when(
        $this->requestGet('lateness') && $this->requestGet('checkInTime'),
        fn(self $that) => $that->baseQuery->whereTime(
          'attendances.signed_in_at',
          $that->requestGet('lateness') === 'late' ? '>' : '<=',
          $that->requestGet('checkInTime')
        )
      );

    if (in_array($this->requestGet('sortKey'), ['firstName', 'lastName'])) {
      $this->joinInstitutionUser(true);
    }

    return $this;
  }
}
