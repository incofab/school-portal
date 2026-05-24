<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\CourseTeacher;
use App\Models\User;

class CoursePolicy
{
  public function __construct()
  {
  }

  public function viewAny(User $user)
  {
    return false;
  }

  public function view(User $user, Course $model)
  {
    return true;
  }

  public function update(User $user, Course $model)
  {
    return $this->view($user, $model);
  }

  public function delete(User $user, Course $model)
  {
    return $this->view($user, $model);
  }

  public function restore(User $user, Course $model)
  {
    return $this->view($user, $model);
  }

  public function viewQuestionBank(?User $user, ?Course $model = null)
  {
    $institutionUser = currentInstitutionUser();
    if (!$institutionUser || !$user) {
      return false;
    }

    if ($institutionUser->isAdmin()) {
      return true;
    }

    if (!$model) {
      return false;
    }

    if ($institutionUser->isTeacher()) {
      $courseTeacher = CourseTeacher::query()
        ->where('institution_id', currentInstitution()?->id)
        ->where('course_id', $model->id)
        ->where('user_id', $user->id)
        ->exists();

      return $courseTeacher;
    }
    return false;
  }
}
