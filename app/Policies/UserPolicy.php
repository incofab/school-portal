<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
  /**
   * Create a new policy instance.
   */
  public function __construct()
  {
    //
  }

  /**
   * Determine whether the user can view any models.
   *
   * @param  \App\Models\User  $user
   * @return \Illuminate\Auth\Access\Response|bool
   */
  public function viewAny(User $user)
  {
    return $user->isInAdminGroup();
  }

  /**
   * Determine whether the user can view the model.
   *
   * @param  \App\Models\User  $user
   * @param  \App\Models\User  $model
   * @return \Illuminate\Auth\Access\Response|bool
   */
  public function view(User $user, User $model)
  {
    if ($model->isSuperAdmin()) {
      if ($user->isSuperAdmin()) {
        return true;
      }

      return false;
    }

    return $user->isInAdminGroup() || $this->update($user, $model);
  }

  public function impersonate(User $user, User $model)
  {
    if ($model->isSuperAdmin()) {
      if ($user->isSuperAdmin()) {
        return true;
      }

      return false;
    }

    return $user->isInAdminGroup();
  }

  public function update(User $user, User $model)
  {
    return $user->isInAdminGroup() ||
      $model->id === $user->id ||
      $model->isCandidateFor($user);
  }

  /**
   * Determine whether the user can delete the model.
   *
   * @param  \App\Models\User  $user
   * @param  \App\Models\User  $model
   * @return \Illuminate\Auth\Access\Response|bool
   */
  public function delete(User $user, User $model)
  {
    return $user->isInAdminGroup() ||
      ($user->is($model) && !$user->roles()->exists());
  }

  /**
   * Determine whether the user can restore the model.
   *
   * @param  \App\Models\User  $user
   * @param  \App\Models\User  $model
   * @return \Illuminate\Auth\Access\Response|bool
   */
  public function restore(User $user, User $model)
  {
    //
  }
}
