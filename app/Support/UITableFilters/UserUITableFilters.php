<?php

namespace App\Support\UITableFilters;

use App\Enums\UserRoleType;
use Illuminate\Validation\Rules\Enum;

class UserUITableFilters extends BaseUITableFilter
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
      'role' => ['sometimes', new Enum(UserRoleType::class)]
    ];
  }

  protected function generalSearch(string $search)
  {
    $this->baseQuery->where(
      fn($q) => $q
        ->where('users.last_name', 'like', "%$search%")
        ->orWhere('users.first_name', 'like', "%$search%")
        ->orWhere('users.other_names', 'like', "%$search%")
        ->orWhere('users.email', 'like', "%$search%")
        ->orWhere('users.phone', 'like', "%$search%")
    );
  }

  protected function joinInstitutionUser(): static
  {
    $this->callOnce(
      'joinInstitutionUser',
      fn() => $this->baseQuery->join(
        'institution_users',
        'users.id',
        'institution_users.user_id'
      )
    );
    return $this;
  }

  protected function directQuery()
  {
    $this->when(
      $this->requestGet('institution_id') || $this->requestGet('role'),
      fn(self $that) => $that->joinInstitutionUser()
    )
      ->baseQuery->when(
        $this->requestGet('first_name'),
        fn($q, $value) => $q->where('users.first_name', 'like', "%$value%")
      )
      ->when(
        $this->requestGet('last_name'),
        fn($q, $value) => $q->where('users.last_name', 'like', "%$value%")
      )
      ->when(
        $this->requestGet('name'),
        fn($q, $value) => $q
          ->where('users.last_name', 'like', "%$value%")
          ->orWhere('users.first_name', 'like', "%$value%")
      )
      ->when(
        $this->requestGet('email'),
        fn($q, $value) => $q->where('users.email', 'like', "%$value%")
      )
      ->when(
        $this->requestGet('institution_id'),
        fn($q, $value) => $q->where('institution_users.institution_id', $value)
      )
      ->when(
        $this->requestGet('role'),
        fn($q, $value) => $q->where('institution_users.role', $value)
      );

    return $this;
  }
}
