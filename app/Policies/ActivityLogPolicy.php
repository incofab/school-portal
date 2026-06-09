<?php

namespace App\Policies;

use App\Models\ActivityLog;
use App\Models\User;

class ActivityLogPolicy
{
  public function viewAnyManager(User $user): bool
  {
    return $user->isAdmin() || $user->can('activity-logs.view-any');
  }

  public function viewAnyInstitution(User $user): bool
  {
    return $user->isInstitutionAdmin() ||
      $user->can('activity-logs.view-institution');
  }

  public function viewInstitution(User $user, ActivityLog $activityLog): bool
  {
    $institution = currentInstitution();

    return $this->viewAnyInstitution($user) &&
      ($activityLog->institution_id === $institution?->id ||
        ($activityLog->institution_id === null &&
          $activityLog->institution_group_id ===
            $institution?->institution_group_id));
  }
}
