<?php

namespace App\Support\UITableFilters;

use App\Enums\InstitutionUserType;
use Illuminate\Validation\Rule;

class StudentUITableFilters extends UserUITableFilters
{
  protected function extraValidationRules(): array
  {
    return [
      ...parent::extraValidationRules(),
      'code' => ['sometimes', 'string'],
      'classification' => ['sometimes', 'integer'],
      'studentRole' => [
        'sometimes',
        Rule::in([
          'all',
          InstitutionUserType::Student->value,
          InstitutionUserType::Alumni->value
        ])
      ]
    ];
  }

  private function joinUser(): static
  {
    $this->callOnce(
      'joinUser',
      fn() => $this->baseQuery->join('users', 'students.user_id', 'users.id')
    );
    return $this;
  }

  // private function joinClassification(): static
  // {
  //   $this->callOnce(
  //     'joinClassification',
  //     fn() => $this->baseQuery->join(
  //       'classifications',
  //       'classifications.id',
  //       'students.classification_id'
  //     )
  //   );
  //   return $this;
  // }

  protected function directQuery()
  {
    $this->joinUser();

    parent::directQuery()
      ->baseQuery->when(
        $this->requestGet('classification'),
        fn($q, $value) => $q->where('students.classification_id', $value)
      )
      ->when(
        $this->requestGet('studentRole', InstitutionUserType::Student->value),
        fn($q, $value) => $value == 'all'
          ? $q
          : $q->where('institution_users.role', $value)
      );

    return $this;
  }
}
