<?php

namespace App\Policies;

use App\Models\BankAccount;
use App\Models\User;

class BankAccountPolicy
{
  /**
   * Create a new policy instance.
   */
  public function __construct()
  {
    //
  }

  public function update(User $user, BankAccount $model)
  {
    return !$model->withdrawals()->exists();
  }

  /**
   * Determine whether the user can delete the model.
   *
   * @param  \App\Models\User  $user
   * @param  \App\Models\BankAccount  $model
   * @return \Illuminate\Auth\Access\Response|bool
   */
  public function delete(User $user, BankAccount $model)
  {
    return $this->update($user, $model);
  }
}
