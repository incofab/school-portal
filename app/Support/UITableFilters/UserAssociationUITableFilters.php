<?php

namespace App\Support\UITableFilters;

class UserAssociationUITableFilters extends BaseUITableFilter
{
  protected array $sortableColumns = [
    'createdAt' => 'created_at'
  ];

  protected function extraValidationRules(): array
  {
    return [
      'institutionUser' => ['sometimes', 'integer'],
      'user' => ['sometimes', 'integer'],
      'association' => ['nullable', 'integer']
    ];
  }

  protected function generalSearch(string $search)
  {
    return $this;
  }

  public function joinInstitutionUser(): static
  {
    $this->callOnce(
      'joinInstitutionUser',
      fn() => $this->baseQuery->join(
        'institution_users',
        'institution_users.id',
        'user_associations.institution_user_id'
      )
    );
    return $this;
  }

  protected function directQuery()
  {
    $this->joinInstitutionUser();

    $this->baseQuery
      ->when(
        $this->requestGet('institution_id'),
        fn($q, $value) => $q->where('user_associations.institution_id', $value)
      )
      ->when(
        $this->requestGet('user'),
        fn($q, $value) => $q->where('institution_users.user_id', $value)
      )
      ->when(
        $this->requestGet('institutionUser'),
        fn($q, $value) => $q->where(
          'user_associations.institution_user_id',
          $value
        )
      )
      ->when(
        $this->requestGet('association'),
        fn($q, $value) => $q->where('user_associations.association_id', $value)
      );

    return $this;
  }
}
