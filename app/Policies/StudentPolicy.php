<?php

namespace App\Policies;

use App\Models\GuardianStudent;
use App\Models\Student;
use App\Models\User;

class StudentPolicy
{
  public function __construct()
  {
  }

  public function viewAny(User $user)
  {
    return false;
  }

  public function view(User $user, Student $model)
  {
    return $user->id === $model->user_id ||
      GuardianStudent::isGuardianOfStudent($user->id, $model->id) ||
      currentInstitutionUser()?->isStaff();
  }

  public function update(User $user, Student $model)
  {
    return $this->view($user, $model);
  }

  public function delete(User $user, Student $model)
  {
    return $this->view($user, $model);
  }

  public function restore(User $user, Student $model)
  {
    return $this->view($user, $model);
  }
}
