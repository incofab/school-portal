<?php

namespace App\Support\Queries;

use Illuminate\Database\Eloquent\Builder;

class StudentQueryBuilder extends Builder
{
  public function joinInstitution(int $institutionId): self
  {
    $this->join(
      'institution_users',
      'students.institution_user_id',
      'institution_users.id'
    )->where('institution_users.institution_id', $institutionId);
    return $this;
  }
}
