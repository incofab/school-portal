<?php

namespace App\Support\UITableFilters;

class StudentUITableFilters extends UserUITableFilters
{
  protected function extraValidationRules(): array
  {
    return [
      ...parent::extraValidationRules(),
      'code' => ['sometimes', 'string'],
      'classification' => ['sometimes', 'integer']
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
    // $this->joinUser()->when(
    //   $this->requestGet('institution_id'),
    //   fn(self $that) => $that->joinClassification()
    // );

    parent::directQuery()->baseQuery->when(
      $this->requestGet('classification'),
      fn($q, $value) => $q->where('students.classification_id', $value)
    );

    return $this;
  }
}
