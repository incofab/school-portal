<?php

namespace App\Policies;

use App\Models\Assignment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AssignmentPolicy
{
  use HandlesAuthorization;
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
    // All users can view assignments, the restriction is done via the controller.
    return true;
  }

  /**
   * Determine whether the user can view the model.
   *
   * @param  \App\Models\User  $user
   * @param  \App\Models\Assignment  $assignment
   * @return \Illuminate\Auth\Access\Response|bool
   */
  public function view(User $user, Assignment $assignment)
  {
    // The logic is done within the controller.
    return true;
  }

  /**
   * Determine whether the user can create models.
   *
   * @param  \App\Models\User  $user
   * @return \Illuminate\Auth\Access\Response|bool
   */
  public function create(User $user)
  {
    // Only Institution Admins and Teachers can create assignments
    return $user->isInstitutionAdmin() || $user->isInstitutionTeacher();
  }

  /**
   * Determine whether the user can update the model.
   *
   * @param  \App\Models\User  $user
   * @param  \App\Models\Assignment  $assignment
   * @return \Illuminate\Auth\Access\Response|bool
   */
  public function update(User $user, Assignment $assignment)
  {
    // Only Institution Admins and the specific Course Teacher can update an assignment
    return $user->isInstitutionAdmin() ||
      ($user->isInstitutionTeacher() &&
        $assignment->courseTeacher->user_id === $user->id);
  }

  /**
   * Determine whether the user can delete the model.
   *
   * @param  \App\Models\User  $user
   * @param  \App\Models\Assignment  $assignment
   * @return \Illuminate\Auth\Access\Response|bool
   */
  public function delete(User $user, Assignment $assignment)
  {
    // Only Institution Admins and the specific Course Teacher can delete an assignment
    return $user->isInstitutionAdmin() ||
      ($user->isInstitutionTeacher() &&
        $assignment->courseTeacher->user_id === $user->id);
  }

  /**
   * Determine whether the user can restore the model.
   *
   * @param  \App\Models\User  $user
   * @param  \App\Models\Assignment  $assignment
   * @return \Illuminate\Auth\Access\Response|bool
   */
  public function restore(User $user, Assignment $assignment)
  {
    // Not implemented for now
    return false;
  }

  /**
   * Determine whether the user can force delete the model.
   *
   * @param  \App\Models\User  $user
   * @param  \App\Models\Assignment  $assignment
   * @return \Illuminate\Auth\Access\Response|bool
   */
  public function forceDelete(User $user, Assignment $assignment)
  {
    // Not implemented for now
    return false;
  }
}
