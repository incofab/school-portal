<?php

namespace App\Http\Controllers\Institutions\Guardians;

use App\Http\Controllers\Controller;
use App\Models\GuardianStudent;
use App\Models\Institution;
use App\Models\Student;
use Illuminate\Http\Request;

class RemoveDependentController extends Controller
{
  public function __invoke(
    Request $request,
    Institution $institution,
    Student $student
  ) {
    abort_unless(
      GuardianStudent::isGuardianOfStudent(
        currentInstitutionUser()->user_id,
        $student->id
      ),
      403
    );

    GuardianStudent::query()
      ->where('guardian_user_id', currentUser()->id)
      ->where('student_id', $student->id)
      ->delete();

    return $this->ok();
  }
}
