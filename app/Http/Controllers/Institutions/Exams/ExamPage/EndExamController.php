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
    ExamHandler::make($exam)->endExam();

    return $this->ok();
  }
}
