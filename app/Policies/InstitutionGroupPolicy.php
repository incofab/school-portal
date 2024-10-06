<?php

namespace App\Policies;

use App\Models\InstitutionGroup;
use App\Models\User;

class InstitutionGroupPolicy
{
  public function update(User $user, InstitutionGroup $model)
  {
    return $model->partner_user_id === $user->id;
  }

  public function delete(User $user, InstitutionGroup $model)
  {
    return $this->update($user, $model);
  }
}
