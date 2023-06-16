<?php

namespace App\Support\Queries;

use Illuminate\Database\Eloquent\Builder;

class InstitutionQueryBuilder extends Builder
{
  public function student(): self
  {
    $this->join(
      'institution_users',
      'institutions.id',
      'institution_users.institution_id'
    )->join('students', 'institution_users.id', 'students.institution_user_id');
    return $this;
  }
}
