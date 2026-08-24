<?php

namespace App\Http\Controllers\Institutions\Exams\ExamPage;

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

    ExamHandler::make($exam)->endExam(boolval($request->re_evaluate));

    return $this->ok();
  }
}
