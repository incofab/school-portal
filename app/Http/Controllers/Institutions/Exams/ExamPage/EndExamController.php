<?php

namespace App\Http\Controllers\Institutions\Exams\ExamPage;

use App\Enums\InstitutionUserType;
use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\Institution;
use App\Support\ExamHandler;
use Illuminate\Http\Request;

class EndExamController extends Controller
{
  function __invoke(Institution $institution, Exam $exam, Request $request)
  {
    abort_unless($exam->institution_id === $institution->id, 404);

    if ($request->boolean('re_evaluate')) {
      abort_unless(
        in_array(
          currentInstitutionUser()?->type,
          [InstitutionUserType::Admin, InstitutionUserType::Teacher],
          true
        ),
        403,
        'You do not have permission to re-evaluate exams'
      );
    }

    ExamHandler::make($exam)->endExam(boolval($request->re_evaluate));

    return $this->ok();
  }
}
