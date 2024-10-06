<?php

namespace App\Policies;

use App\Models\Institution;
use App\Models\User;

class InstitutionPolicy
{
  public function delete(User $user, Institution $model)
  {
    return $model->institutionGroup->partner_user_id === $user->id;
  }
  public function impersonate(User $user, Institution $model)
  {
    return $this->delete($user, $model);
  }
}
