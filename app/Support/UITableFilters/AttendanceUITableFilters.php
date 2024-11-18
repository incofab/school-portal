<?php

namespace App\Support\UITableFilters;

use App\Enums\AttendanceType;
use App\Enums\InstitutionUserType;
use Illuminate\Validation\Rules\Enum;

class AttendanceUITableFilters extends BaseUITableFilter
{
  protected array $sortableColumns = [
    'firstName' => 'first_name',
    'lastName' => 'last_name',
    'email' => 'email',
    'createdAt' => 'created_at'
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
      'roles_not_in' => ['sometimes', 'array'],
      'roles_in' => ['sometimes', 'array']
    ];
  }

  protected function generalSearch(string $search)
  {
    $this->joinInstitutionUser(true)->baseQuery->where(fn($q2) => $q2->where('users.last_name', 'like', "%$search%")
      ->orWhere('users.first_name', 'like', "%$search%"));
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

  protected function directQuery()
  {
    $this->when(
      $this->requestGet('type'),
      fn(self $that, $type) => $that->baseQuery->when($type === AttendanceType::In->value, fn($q) => $q->whereNotNull('signed_in_at'), fn($q) => $q->whereNotNull('signed_out_at'))
    )
      ->when(
        $this->requestGet('role'),
        fn(self $that, $role) => $that->joinInstitutionUser()->baseQuery->where('institution_users.role', $role)
      )->when(
        $this->requestGet('roles_in'),
        fn(self $that, $value) => $that->joinInstitutionUser()->baseQuery->whereIn('institution_users.role', $value)
      )->when(
        $this->requestGet('roles_not_in'),
        fn(self $that, $value) => $that->joinInstitutionUser()->baseQuery->whereNotIn('institution_users.role', $value)
      )->when(
        $this->requestGet('first_name'),
        fn(self $that, $value) => $that->joinInstitutionUser(true)->baseQuery->where('users.first_name', 'like', "%$value%")
      )->when(
        $this->requestGet('last_name'),
        fn(self $that, $value) => $that->joinInstitutionUser(true)->baseQuery->where('users.last_name', 'like', "%$value%")
      )->when(
        $this->requestGet('name'),
        fn(self $that, $value) => $that->joinInstitutionUser(true)->baseQuery->where(fn($q2) => $q2->where('users.last_name', 'like', "%$value%")
          ->orWhere('users.first_name', 'like', "%$value%"))
      );
    return $this;
  }
}