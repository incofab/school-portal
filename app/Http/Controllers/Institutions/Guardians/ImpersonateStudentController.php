<?php

namespace App\Http\Controllers\Institutions\Guardians;

use App\Http\Controllers\Controller;
use App\Models\GuardianStudent;
use App\Models\Institution;
use App\Models\Student;

class ImpersonateStudentController extends Controller
{
  public function __invoke(Institution $institution, Student $student)
  {
    $user = currentUser();
    abort_unless($user?->isInstitutionGuardian(), 403);

    $isGuardian = GuardianStudent::isGuardianOfStudent($user->id, $student->id);
    abort_unless($isGuardian, 403, 'You are not a guardian to this student');

    $student->loadMissing('user');
    abort_unless($student->user, 403, 'Student user not found');

    session([
      'impersonator_id' => $user->id,
      'impersonator_type' => 'guardian',
      'impersonator_institution_id' => $institution->id
    ]);

    auth()->login($student->user);

    return redirect(route('institutions.dashboard', $institution));
  }
}
