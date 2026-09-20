<?php

namespace App\Policies;

use App\Models\User;

class AssistantRunMetricPolicy
{
  /**
   * Assistant diagnostics are platform-wide operational data, so only a
   * manager admin may read them. Partners see their own institutions'
   * activity through the existing activity-log surfaces instead.
   */
  public function viewAnyManager(User $user): bool
  {
    return $user->isAdmin();
  }
}
