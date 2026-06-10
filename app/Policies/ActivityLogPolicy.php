<?php

namespace App\Policies;

use App\Models\ActivityLog;
use App\Models\User;

class ActivityLogPolicy
{
  public function viewAnyManager(User $user): bool
  {
    return $user->isAdmin();
  }

  public function viewAnyInstitution(User $user): bool
  {
    return $user->isInstitutionAdmin();
  }

  public function exportManager(User $user): bool
  {
    return $this->viewAnyManager($user);
  }

  public function exportInstitution(User $user): bool
  {
    return $this->viewAnyInstitution($user);
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
