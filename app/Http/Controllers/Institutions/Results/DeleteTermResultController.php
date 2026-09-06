<?php

namespace App\Http\Controllers\Institutions\Results;

use App\Actions\CourseResult\TermResultDeleteHandler;
use App\Enums\InstitutionUserType;
use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Models\TermResult;
use Illuminate\Http\Request;

class DeleteTermResultController extends Controller
{
  public function __construct()
  {
    $this->allowedRoles([
      InstitutionUserType::Admin,
      InstitutionUserType::Teacher
    ]);
  }

  public function __invoke(
    Request $request,
    Institution $institution,
    TermResult $termResult
  ) {
    $institutionUser = currentInstitutionUser();
    abort_unless(
      $institutionUser->isAdmin() ||
        ($institutionUser->user_id ===
          $termResult->classification?->form_teacher_id),
      403,
      'You can only delete results for your class'
    );

    (new TermResultDeleteHandler($termResult))->delete();
    return $this->ok();
  }
}
