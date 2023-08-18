<?php

namespace App\Http\Controllers\Institutions\Exams\ExamPage;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\Institution;
use App\Support\ExamHandler;
use Illuminate\Http\Request;

class PauseExamController extends Controller
{
  function __invoke(Institution $institution, Exam $exam, Request $request)
  {
    $examHandler = ExamHandler::make($exam);
    if ($examHandler->canRun()) {
      $examHandler->pauseExam();
    }

    return $this->ok();
  }
}
